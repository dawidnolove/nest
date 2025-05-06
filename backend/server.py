from flask import Flask, request, jsonify
from flask_cors import CORS # różne porty więc to na to pozwala
from main import gen_final_answer

app = Flask(__name__) # flask jako główny plik tej apki
CORS(app)# zezwala caej apkiacji na cross origin resource share

@app.route('/api/chat', methods=['POST'])# reaguje tylko na dane otrzymane na serwer
def chat_answer():#każde zapytanie otrzymane na /api/chat wywołuje funkcje
    try:
        user_input = request.json.get('input')
        print(f"User input received: {user_input}")
        
        if not user_input:
            return jsonify({'error': 'Brak tekstu wejściowego'}), 400#bad request bo brak danych

        corrected, intent, answer = gen_final_answer(user_input)

        return jsonify({
            'response': answer,
            'corrected': corrected,
            'intent': intent
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500#wina serwera(nigdy sie nie zdarza)

if __name__ == '__main__':
    app.run(debug=True)