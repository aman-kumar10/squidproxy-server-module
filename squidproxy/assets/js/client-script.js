$(document).ready(function() {
    $('#eyeIcon').click(function() {
        let passwordField = $('#passwordField');
        let isHidden = passwordField.text() === '.................';

        passwordField.html(isHidden ? $('#togglePassword').attr('data-password') : '.................');
        $(this).toggleClass('fa-eye fa-eye-slash');
    });
}); 