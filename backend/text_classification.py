# klasyfikacja tekstu
'''
from text_classification_dict import intent_keywords

def get_intent(text):
    words = text.lower().split()
    
    for intent, keywords in intent_keywords.items():
        for word in words:
            if word in keywords:
                return intent
    return "inne"
'''
import joblib

model = joblib.load('model.pkl')

def get_intent(text):
    prediction = model.predict([text])[0]
    return prediction
