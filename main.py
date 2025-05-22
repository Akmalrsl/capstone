from langchain_community.llms import Ollama
import streamlit as st
import mysql.connector
import re

llm = Ollama(model="phi:latest")

st.title("AI Nutritionist Chatbot")

def get_user_from_db(user_id):
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="healthmate"
    )
    cursor = conn.cursor(dictionary=True)

    query = "SELECT * FROM health_data WHERE id = %s"
    cursor.execute(query, (user_id,))
    result = cursor.fetchone()

    cursor.close()
    conn.close()

    return result

def build_prompt(user):
    return f"""
User Health Profile:
- Age: {user["age"]}
- Gender: {user["gender"]}
- Height: {user["height"]} m
- Weight: {user["weight"]} kg
- BMI: {round(float(user["bmi"]), 2)}
- Blood Pressure: {user["systolicbp"]}/{user["diastolicbp"]}
- Cholesterol Level: {user["cholesterol_level"]}

You are a certified nutritionist chatbot.
Above is a user's health profile. Based on this, generate a daily meal plan that includes:
- Breakfast
- Lunch
- Dinner
- Supper
"""

def display_user_profile(user):
    return f"""
**User Health Profile:**
- Age: {user["age"]}
- Gender: {user["gender"]}
- Height: {user["height"]} m
- Weight: {user["weight"]} kg
- BMI: {round(float(user["bmi"]), 2)}
- Blood Pressure: {user["systolicbp"]}/{user["diastolicbp"]}
- Cholesterol Level: {user["cholesterol_level"]}
"""

def chatbot(user_input):
    match = re.search(r'id (\d+)', user_input.lower())
    if match:
        user_id = match.group(1)
        user_data = get_user_from_db(user_id)
        if user_data:
            prompt = build_prompt(user_data)
            response = llm.invoke(prompt)
            profile_display = display_user_profile(user_data)
            return f"{profile_display}\n\n**AI Suggestion:**\n{response}"
        else:
            return "No user found with that ID."
    else:
        return llm.invoke(user_input)


user_input = st.text_input("Ask something (e.g., 'ID 1', or a health question):")

if user_input:
    with st.spinner("Generating Response..."):
        result = chatbot(user_input)
        st.markdown(result)