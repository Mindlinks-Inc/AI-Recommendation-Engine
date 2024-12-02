// Assuming jQuery is available
$(document).ready(function() {
    let timer;
    const timerDuration = 300; // 5 minutes in seconds

    function startTimer() {
        let timeLeft = timerDuration;
        timer = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timer);
                $("#timer").text("Verification code expired");
                $("#verificationSection").hide();
            } else {
                $("#timer").text(timeLeft + " seconds remaining");
                timeLeft--;
            }
        }, 1000);
    }

    $("#sendVerificationBtn").click(function() {
        if (grecaptcha.getResponse() === "") {
            alert("Please complete the reCAPTCHA");
            return;
        }
        
        // Simulate sending verification code
        $("#verificationSection").show();
        startTimer();
        alert("Verification code sent to your email.");
    });

    $("#confirmBtn").click(function() {
        if ($("#verificationCode").val() !== "") {
            clearInterval(timer);
            $("#nextBtn").prop("disabled", false);
            alert("Verification successful. You can now proceed to the next page.");
        } else {
            alert("Please enter the verification code.");
        }
    });

    $("#userInfoForm").submit(function(e) {
        if ($("#nextBtn").prop("disabled")) {
            e.preventDefault();
            alert("Please complete the email verification process.");
        }
    });
});