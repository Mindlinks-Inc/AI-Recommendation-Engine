// firstname.js

$(document).ready(function() {
    $('#sendVerificationBtn').on('click', function() {
        // Get first name and email
        const firstName = $('#first-name').val().trim();
        const email = $('#email').val().trim();
        
        // Basic validation
        if (!firstName) {
            alert('Please enter your first name');
            return;
        }
        
        // Send verification request
        $.ajax({
            url: '/Assesment-Templates/backend/send_code.php',
            method: 'POST',
            data: {
                'email': email,
                'user_name': firstName  // Using user_name as per your PHP expectation
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#verificationSection').show();
                }
            },
            error: function() {
                alert('An error occurred while sending the verification code.');
            }
        });
    });
});