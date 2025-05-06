# autokorekta
import difflib
from error_correction_dict import words

def correct_text(user_input): # funkcja, split, correct
    user_question_splitted = user_input.split()
    user_question_corrected = []

    for word in user_question_splitted:
        matches = difflib.get_close_matches(word, words, n=1, cutoff=0.7) # pierwsze dopasowanie, min. 70%
        if matches:
            user_question_corrected.append(matches[0])
        else:
            user_question_corrected.append(word)
    corrected_text = " ".join(user_question_corrected)
    return corrected_text