weather_translation = {
    "Clear": "ładne, przejrzyste niebo",
    "Partly cloudy": "jest częściowo zachmurzone",
    "Rain": "pada deszcz",
    "Snow": "śnieg sypie",
    "Cloudy": "jest mocno zachmurzone niebo",
    "Sunny": "jest słonecznie",
    "Windy": "wieje mocny wiatr",
    "Thunderstorm": "burza",
    "Overcast": "zachmurzenie",
    "Light rain": "lekko pada",
}
def translate_weather(weather_english):
    return weather_translation.get(weather_english, weather_english)