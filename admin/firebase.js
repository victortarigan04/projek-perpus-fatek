// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyA1in_9QfKtXCYOSBR41i3BiLihnH-T8uw",
  authDomain: "driven-lore-472406-i6.firebaseapp.com",
  projectId: "driven-lore-472406-i6",
  storageBucket: "driven-lore-472406-i6.firebasestorage.app",
  messagingSenderId: "104682462041",
  appId: "1:104682462041:web:0cc5ba835006f1239bca48",
  measurementId: "G-6MEXY1C8V0"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);