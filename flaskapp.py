#sets up API where php can send health data Flask receives is runs the AI model on it and returns a prediction

from flask import Flask, request, jsonify
#flask is to create the web app, request to receive incoming data and jsonify to return JSON responses
from catboost import CatBoostClassifier

app = Flask(__name__)

model = CatBoostClassifier()
model.load_model("Hypertension_catBoostClassification.cbm")

@app.route('/predict',methods=['POST']) #create a flask route called predict
def predict():
    data = request.get_json()

    #these lines extract each value from the JSON so we can feed them into the AI model
    gender = data['gender']
    age = data['age']
    cholesterol = data['cholesterol_level']
    systolic = data['systolicbp']
    diastolic = data['diastolicbp']
    bmi = data['bmi']

    prediction = model.predict([[gender, age, cholesterol, systolic, diastolic, bmi]])[0]


    return jsonify({'prediction' : int(prediction)}) #THE ERROR WAS CAUSED FROM THE EXTRA SEMICOLON LMAO


if __name__ == '__main__':
    app.run(port=5000)