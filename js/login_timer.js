/**
 * Login lockout timer functionality
 * Displays a countdown timer when user has too many failed login attempts
 */
function initLoginTimer(timerEndTime) {
    function updateTimer() {
        const now = new Date().getTime();
        const distance = timerEndTime - now;
        
        if (distance <= 0) {
            // Timer completed
            document.getElementById("lockoutTimer").innerHTML = "You can try logging in again.";
            document.getElementById("submitBtn").disabled = false;
            clearInterval(timerInterval);
            return;
        }
        
        // Calculate minutes and seconds
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        // Display the result
        document.getElementById("lockoutTimer").innerHTML = 
            "Please wait " + minutes + "m " + seconds + "s before trying again";
        document.getElementById("submitBtn").disabled = true;
    }

    // Update the timer immediately and then every second
    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000);
}