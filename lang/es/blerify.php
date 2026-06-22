<?php
// This file is part of the Blerify Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish strings for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Module metadata.
$string['modulename'] = 'Certificado Blerify';
$string['modulenameplural'] = 'Certificados Blerify';
$string['modulename_help'] = 'El módulo de actividad Certificado Blerify permite emitir Credenciales Verificables W3C a los estudiantes a través de la plataforma Blerify.';
$string['pluginadministration'] = 'Administración de Certificado Blerify';
$string['pluginname'] = 'Certificado Blerify';

// Admin settings.
$string['setting_service_account_json'] = 'JSON de cuenta de servicio';
$string['setting_service_account_json_desc'] = 'Suba el archivo JSON de su cuenta de servicio Blerify (ej. service-account-SA2-sa-XXXXXXX.json). Este archivo contiene todas las credenciales necesarias: client_id, organization_id, private_key, token_uri e iam_audience.';
$string['setting_service_account_upload_help'] = 'Seleccione un archivo .json de cuenta de servicio para importar.';
$string['setting_service_account_current'] = 'Cuenta de servicio actual (solo lectura):';
$string['setting_service_account_invalid_json'] = 'El archivo subido no contiene JSON válido.';

// Activity form.
$string['blerifysettings'] = 'Configuración de Blerify';
$string['projectid'] = 'ID de Proyecto';
$string['projectid_help'] = 'UUID del proyecto Blerify para este certificado';
$string['templateid'] = 'ID de Plantilla';
$string['templateid_help'] = 'UUID de la plantilla de credencial Blerify';
$string['projectid_placeholder'] = 'ej. 60f1a2b3-c4d5-6e7f-8a9b-0c1d2e3f4a5b';
$string['templateid_placeholder'] = 'ej. 50a1b2c3-d4e5-6f7a-8b9c-0d1e2f3a4b5c';
$string['completionissue'] = 'Emitir al completar el curso';
$string['completionissue_help'] = 'Emitir automáticamente un certificado cuando el estudiante complete el curso';

// Validation errors.
$string['error_projectid_required'] = 'El ID de Proyecto es obligatorio';
$string['error_templateid_required'] = 'El ID de Plantilla es obligatorio';

// View page.
$string['viewheader'] = 'Certificados para: {$a}';
$string['nocertificates'] = 'No hay actividades de certificado Blerify en este curso.';
$string['indexheader'] = 'Certificados Blerify en: {$a}';

// Student view.
$string['claim_certificate'] = 'Reclamar Certificado';
$string['certificate_processing'] = 'Tu certificado está siendo procesado. Por favor, vuelve a consultar más tarde.';
$string['certificate_error'] = 'Hubo un error al emitir tu certificado. Por favor, contacta a tu instructor.';
$string['certificate_pending'] = 'Completa el curso para recibir tu certificado digital.';

// Teacher view.
$string['col_student'] = 'Estudiante';
$string['col_email'] = 'Correo electrónico';
$string['col_status'] = 'Estado';
$string['col_credentialid'] = 'ID de Credencial';
$string['col_date'] = 'Fecha de Emisión';
$string['no_credentials_issued'] = 'Aún no se han emitido credenciales.';

// Statuses.
$string['status_pending'] = 'Pendiente';
$string['status_created'] = 'Creada';
$string['status_signed'] = 'Firmada';
$string['status_assembled'] = 'Emitida';
$string['status_authorized'] = 'Habilitado';
$string['status_error'] = 'Error';

// Events.
$string['eventcertificatecreated'] = 'Certificado Blerify creado';

// Privacy.
$string['privacy:metadata:blerify_credentials'] = 'Información sobre las credenciales digitales Blerify emitidas a los usuarios';
$string['privacy:metadata:blerify_credentials:userid'] = 'El ID del usuario receptor de la credencial';
$string['privacy:metadata:blerify_credentials:credentialid'] = 'El identificador externo de la credencial en Blerify';
$string['privacy:metadata:blerify_credentials:status'] = 'El estado actual de la credencial';
$string['privacy:metadata:blerify_credentials:wallet_did'] = 'El DID de la wallet del usuario';
$string['privacy:metadata:blerify_wallet_dids'] = 'DIDs de wallet vinculados a cuentas de usuario';
$string['privacy:metadata:blerify_wallet_dids:userid'] = 'El ID del usuario propietario de la wallet';
$string['privacy:metadata:blerify_wallet_dids:did'] = 'El identificador descentralizado de la wallet del usuario';
$string['privacy:metadata:blerify_wallet_tickets'] = 'Tickets temporales utilizados para el reclamo de credenciales por QR';
$string['privacy:metadata:blerify_wallet_tickets:userid'] = 'El ID del usuario titular del ticket';

// Errors.
$string['error_not_configured'] = 'El plugin Blerify no está configurado correctamente. Por favor, contacta al administrador.';
$string['error_api_call'] = 'Error al comunicarse con la API de Blerify: {$a}';
$string['error_no_wallet_did'] = 'No se puede emitir la credencial: el estudiante no ha vinculado su wallet. Debe escanear el código QR con la app Blerify Wallet primero.';

// Admin config management.
$string['manage_configs'] = 'Gestionar Configuraciones Blerify';
$string['add_config'] = 'Agregar configuración de certificado';
$string['edit_config'] = 'Editar configuración de certificado';
$string['config_name'] = 'Nombre de la configuración';
$string['config_name_placeholder'] = 'ej. Diploma Curso 2024';
$string['config_saved'] = 'Configuración de certificado guardada exitosamente.';
$string['config_deleted'] = 'Configuración de certificado eliminada.';
$string['config_delete_confirm'] = '¿Estás seguro de que deseas eliminar esta configuración?';
$string['existing_configs'] = 'Configuraciones existentes';
$string['no_configs'] = 'Aún no se han creado configuraciones de certificado.';
$string['error_no_config_for_course'] = 'No existe una configuración de certificado Blerify para este curso. Por favor, pide al administrador que configure una.';
$string['error_course_not_found'] = 'El curso seleccionado no fue encontrado.';
$string['error_course_already_configured'] = 'Este curso ya tiene una configuración de certificado Blerify.';
$string['unknowncourse'] = 'Curso desconocido';

// Manual issuance.
$string['issue_credentials'] = 'Habilitar Credenciales';
$string['issue_credentials_header'] = 'Participantes Inscritos';
$string['issue_credentials_help'] = 'Selecciona los participantes a quienes deseas habilitar una credencial digital para que puedan reclamarla. Solo se muestran participantes sin credencial existente.';
$string['issue_selected'] = 'Habilitar a Seleccionados';
$string['no_participants_available'] = 'Todos los participantes inscritos ya tienen una credencial, o no hay participantes inscritos en este curso.';
$string['select_all'] = 'Seleccionar todos';
$string['deselect_all'] = 'Deseleccionar todos';
$string['issue_success'] = '{$a} credencial(es) habilitada(s). El estudiante ya puede reclamarla.';
$string['issue_error_partial'] = '{$a->success} credencial(es) habilitada(s). {$a->fail} fallaron.';
$string['issue_no_selection'] = 'No se seleccionaron participantes.';
$string['col_participant'] = 'Participante';
$string['col_actions'] = 'Acciones';
$string['already_issued'] = 'Ya habilitada';
$string['retry_issue'] = 'Reintentar';
$string['retry_success'] = 'Credencial rehabilitada exitosamente.';
$string['retry_failed'] = 'Reintento fallido. Revisa los registros para más detalles.';

// Wallet connect.
$string['wallet_connect_title'] = 'Conecta tu Blerify Wallet';
$string['wallet_connect_desc'] = 'Escanea el código QR desde tu Wallet e ingresa el código OTP que se envió a tu correo.';
$string['wallet_connect_desc_v2'] = 'Escanea el código QR desde tu Wallet e ingresa el código OTP que se envió a tu correo.';
$string['wallet_download_title'] = 'Descarga tu wallet';
$string['wallet_download_prompt'] = '¿Aún no tienes la wallet? Descárgala aquí:';
$string['wallet_download_ios'] = 'Descargar en App Store';
$string['wallet_download_android'] = 'Obtener en Google Play';
$string['wallet_qr_expires_in'] = 'El código QR expira en: {$a}';
$string['wallet_qr_refresh'] = 'Generar nuevo código QR';
$string['wallet_qr_expired'] = 'El código QR ha expirado.';
$string['wallet_linked_title'] = 'Wallet Conectada';
$string['wallet_linked_desc'] = 'Tu Blerify Wallet ha sido vinculada exitosamente a tu cuenta.';
$string['wallet_error_invalid_token'] = 'Token QR inválido o no reconocido.';
$string['wallet_error_token_used'] = 'Este código QR ya fue utilizado.';
$string['wallet_error_token_expired'] = 'Este código QR ha expirado.';
$string['wallet_error_too_many_attempts'] = 'Demasiados intentos fallidos. Por favor, genera un nuevo código QR.';

// OTP and claim.
$string['wallet_otp_label'] = 'Código de verificación';
$string['wallet_did_current'] = 'Tu DID actual: {$a}';
$string['otp_also_sent_email'] = 'También enviado a tu correo electrónico';
$string['otp_resend_btn'] = 'Reenviar código de verificación';
$string['smtp_not_configured'] = 'Correo SMTP no configurado, por favor contacte con el administrador';
$string['smtp_not_configured_title'] = 'Servicio de correo no configurado';
$string['smtp_not_configured_desc'] = 'Es necesario habilitar el servicio de correo SMTP para poder emitir credenciales, ya que el código de verificación se envía por correo electrónico. Contacta con el administrador del sitio.';
$string['otp_resent'] = 'Se ha enviado un nuevo código de verificación a tu correo electrónico.';
$string['otp_email_subject'] = 'Blerify - Código de verificación';
$string['otp_email_body'] = 'Tu código de verificación para reclamar tu credencial Blerify es: {$a}. Este código expira en 5 minutos.';
$string['otp_email_html_title'] = '¡Código de verificación!';
$string['otp_email_html_greeting'] = '<b>¡Hola!</b><br>Se ha generado un código de verificación para reclamar tu credencial digital en tu billetera <b>Blerify</b>. Ingresa el siguiente código en la plataforma:';
$string['otp_email_html_expiry'] = 'Este código expira en 5 minutos.';
$string['otp_email_html_welcome'] = '¡Bienvenido al futuro, donde eres dueño de tus datos!';
$string['otp_email_html_footer'] = 'Recibes este correo electrónico porque tienes una cuenta de Blerify&trade;. Si no estás seguro de por qué recibes este correo electrónico, contáctanos en <a href="mailto:support@blerify.com" style="text-decoration:none;font-weight:600;color:#2e95d3;">support@blerify.com</a>.';
$string['claim_certificate_btn'] = 'Reclamar tu Certificado';
$string['reclaim_certificate_btn'] = 'Volver a reclamar certificado';
$string['claim_requires_wallet'] = 'Necesitas vincular tu wallet primero';
$string['claim_success'] = 'Credencial emitida exitosamente';
$string['credential_assembled_desc'] = 'Tu credencial ha sido emitida correctamente a tu wallet.';
$string['claim_error_no_did'] = 'No se puede reclamar: wallet no vinculada';
$string['claim_error_already'] = 'Ya tienes una credencial para esta actividad';
$string['wallet_error_invalid_otp'] = 'Código de verificación inválido.';

// Course completion notification.
$string['completion_notification_subject'] = 'Blerify - Tu credencial está lista para reclamar';
$string['completion_notification_body'] = 'Felicitaciones por completar el curso. Tu credencial digital está lista. Visita la actividad de certificado en el curso para reclamarla escaneando el código QR con tu Blerify Wallet.';

// Capabilities.
$string['blerify:addinstance'] = 'Agregar una nueva actividad de Certificado Blerify';
$string['blerify:view'] = 'Ver Certificado Blerify';
$string['blerify:manage'] = 'Gestionar Certificados Blerify';

// Security.
$string['usernotenrolled'] = 'El usuario especificado no está inscrito en este curso.';
