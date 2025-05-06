import datetime
from weather import get_weather

def calculate_time_since(start_date):
    current_date = datetime.datetime.now()
    time_difference = current_date - start_date

    seconds = int(time_difference.total_seconds())
    hours = seconds // 3600
    minutes = (seconds % 3600) // 60
    seconds = seconds % 60
    
    return f"{hours} h {minutes} m {seconds} s"

def generate_answer(intent, corrected_text):
    if intent == "pogoda":
        words = corrected_text.split()
        
        try:
            if "w" in words:
                index = words.index("w")
                city = " ".join(words[index+1:]) #indeks w/pogoda i dzialają wtedy miasta wielowyrazowe
            elif "pogoda" in words:
                index = words.index("pogoda")
                city = " ".join(words[index+1:])
            else:
                city = words[-1]#ALE jak cos dalej mozna zapytac pogda szczecin

            city = city.title()
            return get_weather(city)
        except Exception as e:
            return f"Nie rozumiem o jakie miasto chodzi. ({e})"
        
    elif intent == "blad":
        return "O cholibka, faktycznie, dobra, mój genialny dev się tym zajmie"
    
    elif intent == "haslo":
        return "Jakbyś chciał zmienić to po lewej w ustawieniach, ale inaczej Ci nie pomogę."
    
    elif intent == "pomoc":
        return "Narazie mogę ci pomóc z hasłem albo postowaniem, z resztą musisz byc samodzielny."
    
    elif intent == "komplement":
        return "Tak? No to fajnie."
    
    elif intent == "post":
        return "No na stronie głównej masz przycisk, większego się nie dało zrobić. Piszesz, klikasz załącznik i tyle."
    
    elif intent == "technologie":
        start_date = datetime.datetime(2024, 9, 1, 10, 0, 0)
        return "Sporo roboty, dokładnie to " + calculate_time_since(start_date) + ", tak, tyle minęło od 1 września, no ale nie robiliśmy cały czas. Użyliśmy typowo php, html, css, ajax, a ja stoję na Pythonie."
    
    elif intent == "bezpieczeństwo":
        return "Używam bcrypta do haseł, czy działa? Tak. Ale jeŻeli <? odkryłeś //$injected.sql jakiś exploit, to śmiało pisz /n/n/n ?> na mail..[YOU HAVE BEEN HACKED]"
    
    elif intent == "godzina":
        now = datetime.datetime.now()
        return "Aktualnie godzina " + now.strftime("%H:%M") + " (i masz ją dosłownie pod tym oknem)"
    
    elif intent == "przywitanie":
        return "No cześć, jakieś pytanko?"
    
    elif intent == "pożegnanie":
        return "Trzymaj się!"
    
    elif intent == "negatywne":
        return "Ej ale na spokojnie."

    else:
        return "Nie rozumiem, co chcesz powiedzieć przez " + "\"" + corrected_text + "\""
