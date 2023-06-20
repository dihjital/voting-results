importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyCuXCsziu1yv8qMD0RcDPTM38jsz9dbc7Q",
    projectId: "voting-client-ebb49",
    messagingSenderId: "224379351613",
    appId: "1:224379351613:web:8401ff3d87b74588b97054"
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function({data:{title,body,icon}}) {
    return self.registration.showNotification(title,{body,icon});
});