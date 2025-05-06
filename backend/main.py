# logika czatu
from error_correction import correct_text
from difflib import SequenceMatcher
from text_classification import get_intent
from response_generator import generate_answer

def gen_final_answer(user_input):
    #user_input = input("Pytanie: ")
    corrected = correct_text(user_input) # wywołuje funkcje zaimportowana i przyjmuj text usera

    intent = get_intent(corrected) # klasyfikacja

    answer = generate_answer(intent, corrected) # generowanie odpowiedzi
    print("Poprawione:", corrected)
    print("Intencja:", intent)
    print("Odpowiedź:", answer)

    return corrected, intent, answer
# pogoda nowy jork, przyjmuje tylko jork, nazwa miasta z duzych liter