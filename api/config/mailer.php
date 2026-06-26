<?php
// ============================================================
// api/config/mailer.php
// Envoi d'emails HTML via PHPMailer + Gmail SMTP
// Les templates HTML des emails sont définis en bas du fichier.
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

// ============================================================
// SECTION 1 : Configuration SMTP
// ============================================================

// ⚠️ Remplace ces valeurs par les tiennes avant utilisation
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'vertueuxnoukponkp@gmail.com');   // ← ton adresse Gmail
define('SMTP_PASSWORD', 'frbq dirz vwln qujm');   // ← mot de passe d'application Gmail (16 caractères)
define('SMTP_FROM_NAME','Réseau Social');

// ============================================================
// SECTION 2 : Fonction d'envoi principale
// ============================================================

/**
 * Envoie un email HTML via PHPMailer + Gmail SMTP.
 *
 * @param string $destinataire  Adresse email du destinataire
 * @param string $sujet         Objet de l'email
 * @param string $corpsHtml     Corps de l'email en HTML
 * @return bool                 true si envoyé avec succès, false sinon
 */
function envoyerEmail(string $destinataire, string $sujet, string $corpsHtml): bool {

    $mail = new PHPMailer(true); // true = active les exceptions

    try {
        // --- Configuration du serveur SMTP ---
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS sur port 587
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // --- Expéditeur ---
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);

        // --- Destinataire ---
        $mail->addAddress($destinataire);

        // --- Contenu ---
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $corpsHtml;

        // Version texte brut pour les clients mail qui n'affichent pas le HTML
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $corpsHtml));

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[Mailer] Échec envoi vers ' . $destinataire . ' : ' . $mail->ErrorInfo);
        return false;
    }
}

// ============================================================
// SECTION 3 : Template — Email de vérification d'inscription
// ============================================================

/**
 * Génère le corps HTML de l'email de confirmation de compte.
 */
function templateVerificationEmail(string $prenom, string $lienVerification): string {
    return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
      <meta charset='UTF-8'>
      <meta name='viewport' content='width=device-width, initial-scale=1.0'>
      <title>Confirmez votre email</title>
    </head>
    <body style='margin:0; padding:0; background-color:#f0f2f5; font-family:Arial, sans-serif;'>

      <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f0f2f5; padding:40px 20px;'>
        <tr>
          <td align='center'>
            <table width='600' cellpadding='0' cellspacing='0'
                   style='background:#ffffff; border-radius:10px; overflow:hidden;
                          box-shadow:0 2px 10px rgba(0,0,0,0.1); max-width:600px; width:100%;'>

              <!-- En-tête bleu -->
              <tr>
                <td style='background:#1877f2; padding:35px 40px; text-align:center;'>
                  <h1 style='color:#ffffff; margin:0; font-size:26px; font-weight:bold;'>
                    🌐 Réseau Social
                  </h1>
                </td>
              </tr>

              <!-- Corps -->
              <tr>
                <td style='padding:40px;'>
                  <h2 style='color:#1c1e21; font-size:22px; margin:0 0 16px;'>
                    Bonjour {$prenom} 👋
                  </h2>
                  <p style='color:#606770; font-size:15px; line-height:1.7; margin:0 0 24px;'>
                    Merci de t'être inscrit(e) sur <strong>Réseau Social</strong> !<br>
                    Pour activer ton compte et commencer à te connecter avec tes amis,
                    clique sur le bouton ci-dessous.
                  </p>

                  <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                      <td align='center' style='padding:10px 0 30px;'>
                        <a href='{$lienVerification}'
                           style='background:#1877f2; color:#ffffff; padding:15px 35px;
                                  border-radius:8px; text-decoration:none; font-size:16px;
                                  font-weight:bold; display:inline-block;'>
                          ✅ Confirmer mon adresse email
                        </a>
                      </td>
                    </tr>
                  </table>

                  <p style='color:#606770; font-size:13px; line-height:1.6; margin:0 0 12px;'>
                    Si le bouton ne fonctionne pas, copie ce lien dans ton navigateur :
                  </p>
                  <p style='word-break:break-all; margin:0 0 24px;'>
                    <a href='{$lienVerification}'
                       style='color:#1877f2; font-size:13px;'>{$lienVerification}</a>
                  </p>

                  <hr style='border:none; border-top:1px solid #e4e6eb; margin:24px 0;'>

                  <p style='color:#909399; font-size:13px; margin:0;'>
                    ⏱️ Ce lien expire dans <strong>24 heures</strong>.<br>
                    Si tu n'as pas créé de compte, ignore simplement cet email.
                  </p>
                </td>
              </tr>

              <!-- Pied de page -->
              <tr>
                <td style='background:#f0f2f5; padding:20px 40px; text-align:center;'>
                  <p style='color:#b0b3b8; font-size:12px; margin:0;'>
                    © 2026 Réseau Social — Tous droits réservés
                  </p>
                </td>
              </tr>

            </table>
          </td>
        </tr>
      </table>

    </body>
    </html>";
}

// ============================================================
// SECTION 4 : Template — Email de réinitialisation du mot de passe
// ============================================================

/**
 * Génère le corps HTML de l'email de réinitialisation du mot de passe.
 */
function templateResetPassword(string $prenom, string $lienReset): string {
    return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
      <meta charset='UTF-8'>
      <meta name='viewport' content='width=device-width, initial-scale=1.0'>
      <title>Réinitialisation du mot de passe</title>
    </head>
    <body style='margin:0; padding:0; background-color:#f0f2f5; font-family:Arial, sans-serif;'>

      <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f0f2f5; padding:40px 20px;'>
        <tr>
          <td align='center'>
            <table width='600' cellpadding='0' cellspacing='0'
                   style='background:#ffffff; border-radius:10px; overflow:hidden;
                          box-shadow:0 2px 10px rgba(0,0,0,0.1); max-width:600px; width:100%;'>

              <!-- En-tête rouge -->
              <tr>
                <td style='background:#e74c3c; padding:35px 40px; text-align:center;'>
                  <h1 style='color:#ffffff; margin:0; font-size:26px; font-weight:bold;'>
                    🌐 Réseau Social
                  </h1>
                </td>
              </tr>

              <!-- Corps -->
              <tr>
                <td style='padding:40px;'>
                  <h2 style='color:#1c1e21; font-size:22px; margin:0 0 16px;'>
                    Bonjour {$prenom},
                  </h2>
                  <p style='color:#606770; font-size:15px; line-height:1.7; margin:0 0 24px;'>
                    Nous avons reçu une demande de réinitialisation du mot de passe
                    associé à ton compte <strong>Réseau Social</strong>.<br>
                    Clique sur le bouton ci-dessous pour choisir un nouveau mot de passe :
                  </p>

                  <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                      <td align='center' style='padding:10px 0 30px;'>
                        <a href='{$lienReset}'
                           style='background:#e74c3c; color:#ffffff; padding:15px 35px;
                                  border-radius:8px; text-decoration:none; font-size:16px;
                                  font-weight:bold; display:inline-block;'>
                          🔑 Réinitialiser mon mot de passe
                        </a>
                      </td>
                    </tr>
                  </table>

                  <p style='color:#606770; font-size:13px; line-height:1.6; margin:0 0 12px;'>
                    Si le bouton ne fonctionne pas, copie ce lien dans ton navigateur :
                  </p>
                  <p style='word-break:break-all; margin:0 0 24px;'>
                    <a href='{$lienReset}'
                       style='color:#e74c3c; font-size:13px;'>{$lienReset}</a>
                  </p>

                  <hr style='border:none; border-top:1px solid #e4e6eb; margin:24px 0;'>

                  <p style='color:#909399; font-size:13px; margin:0;'>
                    ⏱️ Ce lien expire dans <strong>1 heure</strong>.<br>
                    Si tu n'es pas à l'origine de cette demande, ignore cet email.
                    Ton mot de passe actuel reste inchangé.
                  </p>
                </td>
              </tr>

              <!-- Pied de page -->
              <tr>
                <td style='background:#f0f2f5; padding:20px 40px; text-align:center;'>
                  <p style='color:#b0b3b8; font-size:12px; margin:0;'>
                    © 2026 Réseau Social — Tous droits réservés
                  </p>
                </td>
              </tr>

            </table>
          </td>
        </tr>
      </table>

    </body>
    </html>";
}