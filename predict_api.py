# Gotta install flask catboost and numpy in a virtual environment first!!
from flask import Flask, request, jsonify
from catboost import CatBoostClassifier
import numpy as np

app = Flask(__name__)

#loading model fom file
model = CatBoostClassifier()
model.load_model("Hypertension_catBoostClassification.cbm")

@app.route('/predict', methods=['POST'])
def predict():
    data = request.get_json()
    print("DEBUG: Received JSON data:", data)

    # Ensure all needed keys are in the input
    expected_keys = ['gender', 'age', 'cholesterol_level', 'systolicbp', 'diastolicbp', 'bmi']
    
    missing = [key for key in expected_keys if key not in data]
    if missing:
        return jsonify({'error': f'Missing fields: {missing}', 'received': data}), 400


    input_data = [[
        data['gender'],
        data['age'],
        data['cholesterol_level'],
        data['systolicbp'],
        data['diastolicbp'],
        data['bmi']
    ]]

    prediction = model.predict(input_data)[0]
    return jsonify({'prediction': int(prediction)})

if __name__ == '__main__':
    app.run(port=5000)
