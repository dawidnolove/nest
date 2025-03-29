function handleCredentialResponse(response){
    fetch("auth.php", { //było auth_init.php
      method: "POST",
      headers: { "Content-Type": "application/json"},
      body: JSON.stringify({request_type: 'user_auth', credential: response.credential })
    })
    .then(response => response.json())
    .then(data => { 
        if(data.status == 1){
          let responsePayload = data.pdata;
          let profileHTML = '<H3>WELCOME, finally '+responsePayload.given_name+'! <a href="javascript:void(0);"onclick="signOut('+responsePayload.sub+');">Sign out</a></h3>';
  
          profileHTML += '<img src="'+responsePayload.picture+'"/<p><b>Auth ID: </b>'+responsePayload.sub+'</p><p><b>Name: </b>'+responsePayload.name+'</p><p><b>Email: </b>'+responsePayload.email+'</p>';
          document.getElementsByClassName("pro-data")[0].innerHTML = profileHTML;
  
          document.querySelector("#google-button").classList.add("hidden");
          document.querySelector(".pro-data").classList.remove("hidden");
  
        }
  
  
    })
    .catch(console.error);
  }
  function signOut(authID){
    document.getElementsByClassName("pro-data")[0].innerHTML = '';
    document.querySelector("#google-button").classList.remove("hidden");
    document.querySelector(".pro-data").classList.add("hidden"); 
  
  }
  
  function handleLogin(event) {
  
    const email = document.querySelector('#login-mail').value;
    const password = document.querySelector('#login-password').value;
  
    if (!validateEmail(email)) {
        alert("Niepoprawny email");
        return;
    }
    if (!validatePassword(password)) {
        alert("Hasło musi mieć minimum 8 znaków, w tym dużą literę i dwie cyfry");
        return;
    }
    fetch('login.php', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `mail=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`,
  })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              window.location.href = 'home.php';
          } else {
              alert(data.message);
              window.location.href = 'index.html';
          }
      })
      .catch(error => {
          console.error('Błąd:', error);
      });
  }
  function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  }
  
  function validatePassword(password) {
    const regex = /^(?=.*[A-Z])(?=(.*\d.*){2,}).{8,}$/;
    return regex.test(password);
  }
  
  document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.querySelector('#login-form');
    loginForm.addEventListener('submit', handleLogin);
  });