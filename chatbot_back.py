from flask import Flask, request, jsonify
from flask_cors import CORS
from langchain_ollama import OllamaLLM
import mysql.connector
import requests
import re
import logging
from collections import defaultdict
from typing import Dict, List, Optional, Any

# === CONFIGURATION ===
MYSQL_HOST = "capstonespring2025.duckdns.org"
MYSQL_USER = "Capstone"
MYSQL_PASSWORD = "Capstone123"
MYSQL_DB = "healthmate"

NUTRITIONIX_APP_ID = "39ced4d8"
NUTRITIONIX_API_KEY = "458858df7c3ee0909d5ce4684d1035c6"

# === APP INIT ===
app = Flask(__name__)
CORS(app)
logging.basicConfig(level=logging.INFO)

llm = OllamaLLM(model="phi:latest")
chat_history: Dict[str, List[str]] = defaultdict(list)
latest_meal_plan: Dict[str, str] = defaultdict(str)
last_user_id: Dict[str, int] = defaultdict(int)
greeted: Dict[str, bool] = defaultdict(bool)

# === DB CONNECTION ===
def get_db_conn() -> mysql.connector.connection.MySQLConnection:
    return mysql.connector.connect(
        host=MYSQL_HOST,
        user=MYSQL_USER,
        password=MYSQL_PASSWORD,
        database=MYSQL_DB,
        port=3306
    )

def fetch_user(user_id: int) -> Optional[Dict[str, Any]]:
    try:
        conn = get_db_conn()
        cur = conn.cursor(dictionary=True)
        cur.execute("SELECT * FROM health_data WHERE id = %s", (user_id,))
        result = cur.fetchone()
        cur.close()
        conn.close()
        return result
    except Exception:
        logging.exception("fetch_user failed")
        return None

def fetch_hypertension_prediction(user_id: int) -> Optional[int]:
    try:
        conn = get_db_conn()
        cur = conn.cursor()
        cur.execute("""
            SELECT prediction_value FROM predictions 
            WHERE health_data_id = %s 
            ORDER BY prediction_time DESC LIMIT 1
        """, (user_id,))
        result = cur.fetchone()
        cur.close()
        conn.close()
        return result[0] if result else None
    except Exception:
        logging.exception("fetch_hypertension_prediction failed")
        return None

def save_plan(user_id: int, plan_text: str, cal: int, carbs: float, protein: float, fat: float) -> None:
    try:
        conn = get_db_conn()
        cur = conn.cursor()
        cur.execute("""
            INSERT INTO meal_plans (user_id, plan_text, total_calories, carbs, protein, fat)
            VALUES (%s, %s, %s, %s, %s, %s)
        """, (user_id, plan_text, cal, carbs, protein, fat))
        conn.commit()
        cur.close()
        conn.close()
    except Exception:
        logging.exception("save_plan failed")

def fetch_latest_plan(user_id: int) -> Optional[Dict[str, Any]]:
    try:
        conn = get_db_conn()
        cur = conn.cursor(dictionary=True)
        cur.execute("""
            SELECT plan_text, total_calories, carbs, protein, fat
            FROM meal_plans
            WHERE user_id = %s
            ORDER BY created_at DESC LIMIT 1
        """, (user_id,))
        result = cur.fetchone()
        cur.close()
        conn.close()
        return result
    except Exception:
        logging.exception("fetch_latest_plan failed")
        return None

def build_prompt(user: Dict[str, Any], risk: Optional[int]) -> str:
    comment = (
        "The user is at high risk of hypertension. Recommend heart-healthy foods only. Avoid high sodium or processed food."
        if risk == 1 else
        "The user is at low risk of hypertension. Suggest a balanced and healthy diet."
    )

    return f"""
You are a certified nutritionist AI.

Health Profile:
- Age: {user['age']}
- Gender: {user['gender']}
- Height: {user['height']} m
- Weight: {user['weight']} kg
- BMI: {round(float(user['bmi']), 2)}
- Blood Pressure: {user['systolicbp']}/{user['diastolicbp']}
- Cholesterol Level: {user['cholesterol_level']}

{comment}

Generate a ONE-DAY meal plan using ONLY real food item names.

DO NOT include:
- emojis
- question marks
- generic placeholders (e.g., "item", "thing", "A 1")
- nutritional advice or extra commentary

Use EXACTLY this format:

Breakfast:
- item 1
- item 2

Lunch:
- item 1
- item 2

Dinner:
- item 1
- item 2

Supper:
- item 1
- item 2
""".strip()

@app.route("/chat", methods=["POST"])
def chat() -> Any:
    try:
        data = request.get_json()
        msg = data.get("message", "").strip()
        session_id = str(data.get("session_id", request.remote_addr))

        if not greeted[session_id]:
            greeted[session_id] = True

        if not msg:
            return jsonify({"reply": "Please enter a message."})

        if msg.lower() in ["hello", "hi", "hey"]:
            return jsonify({"reply": "Hello I'm Nutritionist Chatbot for Healthmate. I can help you with anything, but if you want a meal suggestion, you need to enter ID to get a specific session."})

        if msg.lower() in ["end session", "bye", "goodbye"]:
            last_user_id.pop(session_id, None)
            greeted[session_id] = False
            return jsonify({"reply": "Session ended. Thank you for using the Nutritionist Chatbot."})

        if msg.lower() == "repeat":
            user_id = last_user_id.get(session_id)
            if not user_id:
                return jsonify({"reply": "No previous user ID found in session."})
            existing = fetch_latest_plan(user_id)
            if not existing:
                return jsonify({"reply": "No previous meal found in database."})
            return jsonify({"reply": existing['plan_text']})

        if msg.lower() == "new":
            user_id = last_user_id.get(session_id)
            if not user_id:
                return jsonify({"reply": "No previous user ID found in session."})
            return generate_new_meal(user_id, session_id)

        if msg.lower().strip() in ["profile", "show profile", "display profile"]:
            user_id = last_user_id.get(session_id)
            if not user_id:
                return jsonify({"reply": "No user profile found. Please enter your ID first like 'id 1'."})
            user = fetch_user(user_id)
            if user:
                reply = (
                    f"\U0001F464 User Profile\n"
                    f"- ID: {user['id']}\n"
                    f"- Age: {user['age']}\n"
                    f"- Gender: {user['gender'].capitalize()}\n"
                    f"- Height: {user['height']} m\n"
                    f"- Weight: {user['weight']} kg\n"
                    f"- BMI: {round(float(user['bmi']), 2)}\n"
                    f"- Blood Pressure: {user['systolicbp']}/{user['diastolicbp']}\n"
                    f"- Cholesterol Level: {user['cholesterol_level']}\n"
                )
                return jsonify({"reply": reply})
            else:
                return jsonify({"reply": "User profile not found."})

        match = re.search(r'id (\d+)', msg.lower())
        if match:
            user_id = int(match.group(1))
            last_user_id[session_id] = user_id
            existing = fetch_latest_plan(user_id)
            if existing:
                return jsonify({"reply": f"You have entered ID {user_id}.\nA meal plan already exists.\nType 'repeat' to reuse it or 'new' to generate a new one.\nYou can also type 'show profile' to view your health data."})
            else:
                return generate_new_meal(user_id, session_id)

        llm_reply = llm.invoke(msg)
        full_reply = llm_reply.strip()
        return jsonify({"reply": full_reply})

    except Exception:
        logging.exception("CHAT ERROR")
        return jsonify({"reply": "Server error occurred."}), 500

def generate_new_meal(user_id: int, session_id: str) -> Any:
    user = fetch_user(user_id)
    if not user:
        return jsonify({"reply": "User not found."})

    risk = fetch_hypertension_prediction(user_id)
    prompt = build_prompt(user, risk)
    plan = llm.invoke(prompt)

    sections = re.split(r"(Breakfast:|Lunch:|Dinner:|Supper:)", plan)
    meals: Dict[str, List[str]] = {"Breakfast": [], "Lunch": [], "Dinner": [], "Supper": []}
    for i in range(1, len(sections), 2):
        label = sections[i].strip(':\n ')
        items = [line.strip('- ').strip() for line in sections[i + 1].splitlines() if line.strip()]
        meals[label] = items

    all_items = [
        item for sub in meals.values() for item in sub
        if item and re.search(r'[a-zA-Z]', item) and "?" not in item and len(item) > 3
    ]

    if not all_items:
        return jsonify({"reply": "No meal items found to send to Nutritionix."})

    nutri_response = requests.post(
        "https://trackapi.nutritionix.com/v2/natural/nutrients",
        headers={
            "x-app-id": NUTRITIONIX_APP_ID,
            "x-app-key": NUTRITIONIX_API_KEY,
            "Content-Type": "application/json"
        },
        json={"query": ", ".join(all_items)},
        timeout=10
    )

    if nutri_response.status_code != 200:
        return jsonify({"reply": f"Nutritionix API failed. Status: {nutri_response.status_code}"}), 500

    nutri = nutri_response.json().get("foods", [])
    if not nutri:
        return jsonify({"reply": "No nutritional data found for the items."}), 500

    risk_msg = (
        "This user is at **high risk of hypertension**. Here's a heart-friendly meal plan.\n\n"
        if risk == 1
        else "This user is at **low risk of hypertension**. Here's a balanced meal plan.\n\n"
    )

    reply = risk_msg
    total_cal = carbs = protein = fat = 0
    for label in meals:
        reply += f"{label}:::\n"
        for item in meals[label]:
            match = next((f for f in nutri if f["food_name"].lower() in item.lower()), None)
            if match:
                kcal = round(match["nf_calories"])
                reply += f"- {match['food_name'].capitalize()} ({kcal} kcal)\n"
                total_cal += match["nf_calories"]
                carbs += match["nf_total_carbohydrate"]
                protein += match["nf_protein"]
                fat += match["nf_total_fat"]
            else:
                reply += f"- {item} (? kcal)\n"
        reply += "\n"

    reply += f"Total Calories: {round(total_cal)} kcal\n"
    reply += f"Carbs: {round(carbs)}g | Protein: {round(protein)}g | Fat: {round(fat)}g"

    latest_meal_plan[session_id] = reply.strip()
    save_plan(user_id, reply.strip(), round(total_cal), round(carbs), round(protein), round(fat))
    return jsonify({"reply": reply.strip()})

if __name__ == "__main__":
    app.run(debug=True)
