$(document).ready(function() {
    let verificationSent = false;
    let verificationConfirmed = false;

    // Handle Send Verification Button Click
    $('#sendVerificationBtn').on('click', function() {
        var email = $('#email').val();
        if (email) {
            // Send AJAX request to the server for sending the verification code
            $.post('/Assesment-Templates/backend/send_code.php', { email: email }, function(response) {
                if (response.includes('Code sent')) {
                    alert(response);
                    verificationSent = true;
                    // Show the verification code input and confirm button
                    $('#verificationSection').show();
                    $('#sendVerificationBtn').prop('disabled', true); // Disable sending code again
                    startTimer(300); // Start a 5-minute timer
                } else {
                    alert('Failed to send verification code. Please try again.');
                }
            }).fail(function() {
                alert('Error: Could not reach the server. Please try again later.');
            });
        } else {
            alert('Please enter a valid email address.');
        }
    });

    // Handle Confirm Button Click
    $('#confirmBtn').on('click', function() {
        var code = $('#verificationCode').val();
        if (code) {
            // Send AJAX request to verify the code
            $.post('/Assesment-Templates/backend/verify_code.php', { verification_code: code }, function(response) {
                if (response === 'success') {
                    alert('Verification successful!');
                    verificationConfirmed = true;
                    $('#nextBtn').prop('disabled', false); // Enable the Next button
                    // Mark email as verified in session
                    $.post('/Assesment-Templates/backend/set_email_verified.php', function() {});
                } else {
                    alert('Invalid code. Please try again.');
                }
            }).fail(function() {
                alert('Error: Could not reach the server. Please try again later.');
            });
        } else {
            alert('Please enter the verification code.');
        }
    });

    // Timer function
    function startTimer(duration) {
        var timer = duration, minutes, seconds;
        var intervalId = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            $('#timer').text(minutes + ":" + seconds);

            if (--timer < 0) {
                clearInterval(intervalId);
                $('#timer').text("Time expired. Please request a new code.");
                $('#confirmBtn').prop('disabled', true);
            }
        }, 1000);
    }

    // Ensure form cannot be submitted unless email is verified
    $('#nextBtn').on('click', function(e) {
        if (!verificationSent || !verificationConfirmed) {
            e.preventDefault();
            alert('Please complete the email verification process.');
        }
    });
});
