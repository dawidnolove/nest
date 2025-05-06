import pip._vendor.requests as requests
from weather_dict import translate_weather
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.linear_model import LogisticRegression
import joblib

def get_weather(city):
    api_key = "5433084e3526412e8a7175534250804"
    base_url = "https://api.weatherapi.com/v1/current.json"
    #https://api.weatherapi.com/v1/current.json?key=5433084e3526412e8a7175534250804&q=London&aqi=no

    params = {
        "key": api_key,
        "q": city,
        "aqi": "no"
    }
    response = requests.get(base_url, params=params)

    if response.status_code == 200:
        data = response.json()
        temperature = data["current"]["temp_c"]
        weather_description = data["current"]["condition"]["text"]

        weather_polish = translate_weather(weather_description)

        return f"Pogoda w {city}: {temperature}C, {weather_polish}"
    else:
        return "Na końcu zdania podaj REALNE miasto."

