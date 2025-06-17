from flask import Flask, request, jsonify
from catboost import CatBoostClassifier

app = Flask(__name__)

model = CatBoostClassifier()
model.load_model("hypertension_catboost_Improved_v1.cbm") 

@app.route('/predict', methods=['POST']) #the 'predict' endpoint, maps specific  URLs to python functions
def predict():
    data = request.get_json()

    # Base input features
    gender = data['gender']
    age = data['age']
    cholesterol = data['cholesterol_level']
    systolic = data['systolicbp']
    diastolic = data['diastolicbp']
    bmi = data['bmi']

    # Engineered features
    pulse_pressure = systolic - diastolic
    bp_ratio = systolic / diastolic if diastolic != 0 else 0
    age_bmi_index = age * bmi
    cholesterol_flag = 1 if cholesterol == 3 else 0

    def bmi_category_calc(bmi):
        if bmi < 18.5:
            return 0
        elif bmi < 25:
            return 1
        elif bmi < 30:
            return 2
        else:
            return 3

    bmi_category = bmi_category_calc(bmi)

    # All features in order
    features = [
        gender, age, cholesterol, systolic, diastolic, bmi,
        pulse_pressure, bp_ratio, age_bmi_index,
        cholesterol_flag, bmi_category
    ]

    prediction = model.predict([features])[0]
    return jsonify({'prediction': int(prediction)}) #changes to int format and send prediction value (0,1)

if __name__ == '__main__':
    app.run(port=5000)
