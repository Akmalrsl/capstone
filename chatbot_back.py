from flask import Flask, request, jsonify
from flask_cors import CORS
from langchain_community.llms import Ollama
import mysql.connector
import re
import requests
from collections import defaultdict

app = Flask(__name__)
CORS(app)
llm = Ollama(model="phi:latest")

NUTRITIONIX_APP_ID = "39ced4d8"
NUTRITIONIX_API_KEY = "dc80bad1093e3611d655bb6f0f0b4579"

chat_history = defaultdict(list)  # session_id -> list of messages
latest_meal_plan = defaultdict(str)  # session_id -> last meal plan response


def get_user_from_db(user_id):
    conn = mysql.connector.connect(
        host="capstonespring2025.duckdns.org",
        port=3306,
        user="Capstone",
        password="Capstone123",
        database="healthmate"
    )
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM health_data WHERE id = %s", (user_id,))
    result = cursor.fetchone()
    cursor.close()
    conn.close()
    return result


def build_prompt(user):
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
    data = request.get_json()
    user_input = data.get("message", "").strip()
    session_id = "default"

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
    if match:
        user_id = match.group(1)
        user_data = get_user_from_db(user_id)
        if not user_data:
            return jsonify({"reply": "No user found with that ID."})

        prompt = build_prompt(user_data)
        raw_plan = llm.invoke(prompt)
        sections = re.split(r"(Breakfast:|Lunch:|Dinner:|Supper:)", raw_plan)

        meal_items_by_section = {}
        for i in range(1, len(sections), 2):
            label = sections[i].strip()
            items_block = sections[i + 1].strip()
            lines = [line.strip("- ").strip() for line in items_block.splitlines() if line.strip()]
            meal_items_by_section[label] = lines

        all_items = [item for sublist in meal_items_by_section.values() for item in sublist]
        nutri_resp = requests.post(
            "https://trackapi.nutritionix.com/v2/natural/nutrients",
            headers={
                "x-app-id": NUTRITIONIX_APP_ID,
                "x-app-key": NUTRITIONIX_API_KEY,
                "Content-Type": "application/json"
            },
            json={"query": ", ".join(all_items)}
        )

        foods_data = nutri_resp.json().get("foods", [])
        final_reply = ""
        total_cal, carbs, protein, fat = 0, 0, 0, 0

        for label, items in meal_items_by_section.items():
            final_reply += f"{label}::\n"
            for item in items:
                match = next((f for f in foods_data if f["food_name"].lower() in item.lower()), None)
                if match:
                    kcal = round(match["nf_calories"])
                    total_cal += match["nf_calories"]
                    carbs += match.get("nf_total_carbohydrate", 0)
                    protein += match.get("nf_protein", 0)
                    fat += match.get("nf_total_fat", 0)
                    final_reply += f"- {match['food_name'].capitalize()} ({kcal} kcal)\n"
                else:
                    final_reply += f"- {item} (? kcal)\n"
            final_reply += "\n"

        final_reply += f"Total Calories: {round(total_cal)} kcal\n"
        final_reply += "Estimated Macronutrient Breakdown:\n"
        final_reply += f"- Carbs: {round(carbs)}g\n"
        final_reply += f"- Protein: {round(protein)}g\n"
        final_reply += f"- Fat: {round(fat)}g"

        latest_meal_plan[session_id] = final_reply.strip()
        return jsonify({"reply": final_reply.strip()})

    # --- context-aware ---
    chat_history[session_id].append({"role": "user", "content": user_input})
    if latest_meal_plan[session_id] and "replace" in user_input.lower():
        context_prompt = (
            "You are a nutritionist assistant.\n"
            "You will be given a meal plan and a simple instruction from the user to modify it.\n"
            "You MUST only return the updated full meal plan with the change applied.\n"
            "Do NOT explain or reason. Do NOT add health advice.\n"
            f"Meal Plan:\n{latest_meal_plan[session_id]}\n"
            f"User Instruction: {user_input}\n"
            "Now return the full updated meal plan in this format:\n"
            "Breakfast:\n- item (kcal)\nLunch:\n- item (kcal)\nDinner:\n- item (kcal)\nSupper:\n- item (kcal)"
        )
    else:
        context_prompt = "You are a helpful nutritionist chatbot. Continue this conversation:\n"
        for msg in chat_history[session_id][-6:]:
            context_prompt += f"{msg['role'].capitalize()}: {msg['content']}\n"
        context_prompt += "Assistant:"

    response = llm.invoke(context_prompt)
    chat_history[session_id].append({"role": "assistant", "content": response})
    return jsonify({"reply": response})


if __name__ == "__main__":
    app.run(port=8000)
