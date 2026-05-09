<?php
require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendVerificationCode(string $toEmail, string $code, string $context = 'reset'): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_APP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail);

        if ($context === 'signup') {
            $mail->Subject = 'DarQuest — Vérification de votre adresse courriel';
            $body = "Bonjour,\r\n\r\n"
                  . "Merci de créer un compte sur DarQuest !\r\n\r\n"
                  . "Votre code de vérification : {$code}\r\n\r\n"
                  . "Ce code est valide pendant 10 minutes.\r\n"
                  . "Si vous n'avez pas demandé ce compte, ignorez ce message.\r\n\r\n"
                  . "L'équipe DarQuest";
        } elseif ($context === 'change') {
            $mail->Subject = 'DarQuest — Confirmation de changement de mot de passe';
            $body = "Bonjour,\r\n\r\n"
                  . "Vous avez demandé à changer votre mot de passe sur DarQuest.\r\n\r\n"
                  . "Votre code de confirmation : {$code}\r\n\r\n"
                  . "Ce code est valide pendant 10 minutes.\r\n"
                  . "Si vous n'avez pas fait cette demande, ignorez ce message — votre mot de passe reste inchangé.\r\n\r\n"
                  . "L'équipe DarQuest";
        } else {
            $mail->Subject = 'DarQuest — Réinitialisation de mot de passe';
            $body = "Bonjour,\r\n\r\n"
                  . "Vous avez demandé à réinitialiser votre mot de passe sur DarQuest.\r\n\r\n"
                  . "Votre code de vérification : {$code}\r\n\r\n"
                  . "Ce code est valide pendant 10 minutes.\r\n"
                  . "Si vous n'avez pas fait cette demande, ignorez ce message.\r\n\r\n"
                  . "L'équipe DarQuest";
        }

        $mail->isHTML(false);
        $mail->Body = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}
