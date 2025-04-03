<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.i4ware.fi'; // Vaihda oikeaksi SMTP-palvelimeksi
        $mail->SMTPAuth = false; // Ota käyttöön, jos SMTP vaatii todennuksen
        $mail->SMTPAutoTLS = false; // Ota käyttöön, jos SMTP vaatii TLS-salauksen
        $mail->Username = ''; // SMTP-käyttäjätunnus, jos käytössä
        $mail->Password = '';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom($_POST["email"], $_POST["firstname"] . ' ' . $_POST["lastname"]);
        $mail->addAddress("info@i4ware.fi");

        $mail->Subject = "Uusi yhteydenottopyyntö - " . $_POST["firstname"];
        $mail->Body = "Viesti:\n" . $_POST["message"];

        $mail->send();
        echo "Viesti lähetetty onnistuneesti!";
    } catch (Exception $e) {
        echo "Virhe: " . $mail->ErrorInfo;
    }
}
?>