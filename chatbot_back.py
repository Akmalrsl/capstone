from flask import Flask, request, jsonify
from flask_cors import CORS
from langchain_ollama import OllamaLLM
import mysql.connector
import re
import requests
from collections import defaultdict
from typing import Dict, List, DefaultDict, Any, Optional, cast

app = Flask(__name__)
CORS(app)

llm = OllamaLLM(model="phi:latest")

NUTRITIONIX_APP_ID = "39ced4d8"
NUTRITIONIX_API_KEY = "dc80bad1093e3611d655bb6f0f0b4579"

chat_history: DefaultDict[str, List[Dict[str, str]]] = defaultdict(list)
latest_meal_plan: DefaultDict[str, str] = defaultdict(str)

def get_user_from_db(user_id: int) -> Optional[Dict[str, Any]]:
    try:
        conn = mysql.connector.connect(
            host="capstonespring2025.duckdns.org",
            port=3306,
            user="Capstone",
            password="Capstone123",
            database="healthmate"
        )
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM health_data WHERE id = %s", (user_id,))
        result = cast(Optional[Dict[str, Any]], cursor.fetchone())
        cursor.close()
        conn.close()
        return result
    except Exception as e:
        print("❌ DB error:", e)
        return None

def get_hypertension_risk(user_id: int) -> Optional[int]:
    try:
        conn = mysql.connector.connect(
            host="capstonespring2025.duckdns.org",
            user="Capstone",
            password="Capstone123",
            database="healthmate"
        )
        cursor = conn.cursor()
        cursor.execute("""
            SELECT p.prediction_value
            FROM predictions p
            JOIN health_data h ON p.health_data_id = h.id
            WHERE h.id = %s
            ORDER BY p.prediction_time DESC
            LIMIT 1
        """, (user_id,))
        result = cursor.fetchone()
        cursor.close()
        conn.close()
        return result[0] if result else None
    except Exception as e:
        print("❌ Error fetching prediction:", e)
        return None

def get_latest_meal_plan(user_id: int) -> Optional[Dict[str, Any]]:
    try:
        conn = mysql.connector.connect(
            host="capstonespring2025.duckdns.org",
            port=3306,
            user="Capstone",
            password="Capstone123",
            database="healthmate"
        )
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT plan_text, total_calories, carbs, protein, fat 
            FROM meal_plans 
            WHERE user_id = %s 
            ORDER BY created_at DESC 
            LIMIT 1
        """, (user_id,))
        result = cursor.fetchone()
        cursor.close()
        conn.close()
        return result
    except Exception as e:
        print("❌ Error fetching meal plan:", e)
        return None

def save_meal_plan(user_id: int, plan_text: str, total_cal: int, carbs: float, protein: float, fat: float):
    try:
        conn = mysql.connector.connect(
            host="capstonespring2025.duckdns.org",
            user="Capstone",
            password="Capstone123",
            database="healthmate"
        )
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO meal_plans (user_id, plan_text, total_calories, carbs, protein, fat)
            VALUES (%s, %s, %s, %s, %s, %s)
        """, (user_id, plan_text, total_cal, carbs, protein, fat))
        conn.commit()
        cursor.close()
        conn.close()
    except Exception as e:
        print("❌ Failed to save meal plan:", e)

def build_prompt(user: Dict[str, Any], prediction: Optional[int]) -> str:
    prediction_comment = (
        "The user is at high risk of hypertension. Avoid processed foods, excess salt, and saturated fat. "
        "Prioritize fruits, vegetables, whole grains, and lean protein."
        if prediction == 1 else
        "The user has low risk of hypertension. A general balanced diet is suitable."
    )

    return f"""
You are a certified nutritionist chatbot.

This is the health profile of a user:
- Age: {user["age"]}
- Gender: {user["gender"]}
- Height: {user["height"]} m
- Weight: {user["weight"]} kg
- BMI: {round(float(user["bmi"]), 2)}
- Blood Pressure: {user["systolicbp"]}/{user["diastolicbp"]}
- Cholesterol Level: {user["cholesterol_level"]}

{prediction_comment}

Based on this profile, generate a one-day meal plan. Format it like this:
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

Do not include explanations, quantities, or numbers. Only return the food names.
"""

@app.route("/chat", methods=["POST"])
def chat():
    try:
        data: Dict[str, Any] = request.get_json()
        user_input: str = data.get("message", "").strip()
        session_id: str = "default"

        if not user_input:
            return jsonify({"reply": "Invalid input."}), 400

        if user_input.lower() in ["reset", "clear history"]:
            chat_history[session_id] = []
            latest_meal_plan[session_id] = ""
            return jsonify({"reply": "✅ Chat history cleared."})

        if any(kw in user_input.lower() for kw in ["repeat meal", "my meal plan", "send back meal", "repeat the plan"]):
            if latest_meal_plan[session_id]:
                return jsonify({"reply": latest_meal_plan[session_id]})
            else:
                return jsonify({"reply": "❌ No previous meal plan found. Try something like 'id 22' first."})

        match = re.search(r'id (\d+)', user_input.lower())
        force_new = re.search(r'new meal for id (\d+)', user_input.lower())

        if match:
            user_id: int = int(match.group(1))
            force_generate = bool(force_new and int(force_new.group(1)) == user_id)

            if not force_generate:
                existing_plan = get_latest_meal_plan(user_id)
                if existing_plan:
                    return jsonify({"reply": f"📋 A meal plan already exists for this user.\n\nIf you want a new one, just type: 'new meal for id {user_id}'.\nTo see the old one again, type: 'repeat meal'."})

            user_data = get_user_from_db(user_id)
            if not user_data:
                return jsonify({"reply": "No user found with that ID."})

            prediction = get_hypertension_risk(user_id)
            prompt = build_prompt(user_data, prediction)
            raw_plan: str = llm.invoke(prompt)

            meal_items_by_section: Dict[str, List[str]] = {}
            expected_sections = ["Breakfast", "Lunch", "Dinner", "Supper"]
            for section in expected_sections:
                meal_items_by_section[section] = []

            sections = re.split(r"(Breakfast:|Lunch:|Dinner:|Supper:)", raw_plan)
            for i in range(1, len(sections), 2):
                label = sections[i].strip().replace(":", "")
                items_block = sections[i + 1].strip()
                lines = [line.strip("- ").strip() for line in items_block.splitlines() if line.strip()]
                meal_items_by_section[label] = lines

            all_items: List[str] = [item for sublist in meal_items_by_section.values() for item in sublist]

            nutri_resp = requests.post(
                "https://trackapi.nutritionix.com/v2/natural/nutrients",
                headers={
                    "x-app-id": NUTRITIONIX_APP_ID,
                    "x-app-key": NUTRITIONIX_API_KEY,
                    "Content-Type": "application/json"
                },
                json={"query": ", ".join(all_items)}
            )
            nutri_resp.raise_for_status()
            foods_data = nutri_resp.json().get("foods", [])

            final_reply = ""
            total_cal, carbs, protein, fat = 0, 0, 0, 0

            for label in expected_sections:
                final_reply += f"{label}:::\n"
                for item in meal_items_by_section[label]:
                    match_food = next((f for f in foods_data if f["food_name"].lower() in item.lower()), None)
                    if match_food:
                        kcal = round(match_food["nf_calories"])
                        total_cal += match_food["nf_calories"]
                        carbs += match_food.get("nf_total_carbohydrate", 0)
                        protein += match_food.get("nf_protein", 0)
                        fat += match_food.get("nf_total_fat", 0)
                        final_reply += f"- {match_food['food_name'].capitalize()} ({kcal} kcal)\n"
                    else:
                        final_reply += f"- {item} (? kcal)\n"
                final_reply += "\n"

            if prediction == 1:
                final_reply = "⚠️ This user is at high risk of hypertension. Suggested meal plan is designed to reduce sodium and saturated fats.\n\n" + final_reply
            elif prediction == 0:
                final_reply = "✅ This user is at low risk of hypertension. Here's a balanced meal plan.\n\n" + final_reply

            final_reply += f"Total Calories: {round(total_cal)} kcal\n"
            final_reply += "Estimated Macronutrient Breakdown:\n"
            final_reply += f"- Carbs: {round(carbs)}g\n"
            final_reply += f"- Protein: {round(protein)}g\n"
            final_reply += f"- Fat: {round(fat)}g"

            latest_meal_plan[session_id] = final_reply.strip()

            save_meal_plan(
                user_id=user_id,
                plan_text=latest_meal_plan[session_id],
                total_cal=round(total_cal),
                carbs=round(carbs),
                protein=round(protein),
                fat=round(fat)
            )

            return jsonify({"reply": latest_meal_plan[session_id]})

        chat_history[session_id].append({"role": "user", "content": user_input})
        context_prompt = "You are a helpful nutritionist chatbot. Continue this conversation:\n"
        for msg in chat_history[session_id][-6:]:
            context_prompt += f"{msg['role'].capitalize()}: {msg['content']}\n"
        context_prompt += "Assistant:"

        response: str = llm.invoke(context_prompt)
        chat_history[session_id].append({"role": "assistant", "content": response})
        return jsonify({"reply": response})

    except Exception as e:
        print("🔥 Error in /chat route:", e)
        return jsonify({"reply": f"❌ Server error: {str(e)}"}), 500

if __name__ == "__main__":
    app.run(port=8888, debug=True)
