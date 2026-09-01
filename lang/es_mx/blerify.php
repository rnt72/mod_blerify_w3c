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
 * Spanish (Mexico) strings for mod_blerify
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
$string['completionissue'] = 'Emitir al completar el curso';
$string['completionissue_help'] = 'Emitir automáticamente un certificado cuando el estudiante complete el curso';

// Validation errors.
$string['error_projectid_required'] = 'El ID de Proyecto es obligatorio';

// View page.
$string['viewheader'] = 'Certificados para: {$a}';
$string['nocertificates'] = 'No hay actividades de certificado Blerify en este curso.';
$string['indexheader'] = 'Certificados Blerify en: {$a}';

// Student view.
$string['certificate_processing'] = 'Tu certificado está siendo procesado. Por favor, vuelve a consultar más tarde.';
$string['certificate_error'] = 'Hubo un error al emitir tu certificado. Por favor, contacta a tu instructor.';

// Teacher view.
$string['col_student'] = 'Estudiante';
$string['col_email'] = 'Correo electrónico';
$string['col_status'] = 'Estado';
$string['col_credentialid'] = 'ID de Credencial';
$string['col_date'] = 'Fecha de Emisión';
$string['no_credentials_issued'] = 'Aún no se han emitido credenciales.';

// Statuses.
$string['status_pending'] = 'Pendiente';
$string['status_error'] = 'Error';

// Events.
$string['eventcertificatecreated'] = 'Certificado Blerify creado';

// Privacy.
$string['privacy:metadata:blerify_credentials'] = 'Información sobre las credenciales digitales Blerify emitidas a los usuarios';
$string['privacy:metadata:blerify_credentials:userid'] = 'El ID del usuario receptor de la credencial';
$string['privacy:metadata:blerify_credentials:credentialid'] = 'El identificador externo de la credencial en Blerify';
$string['privacy:metadata:blerify_credentials:status'] = 'El estado actual de la credencial';

// Errors.
$string['error_not_configured'] = 'El plugin Blerify no está configurado correctamente. Por favor, contacta al administrador.';
$string['error_api_call'] = 'Error al comunicarse con la API de Blerify: {$a}';

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
$string['retry_issue'] = 'Reintentar';
$string['retry_success'] = 'Credencial rehabilitada exitosamente.';
$string['retry_failed'] = 'Reintento fallido. Revisa los registros para más detalles.';

// Wallet connect.
$string['wallet_download_prompt'] = '¿Aún no tienes la wallet? Descárgala aquí:';
$string['wallet_download_ios'] = 'Descargar en App Store';
$string['wallet_download_android'] = 'Obtener en Google Play';

// OTP and claim.

// Course completion notification.
$string['completion_notification_subject'] = 'Blerify - Tu credencial está lista para reclamar';
$string['completion_notification_body'] = 'Felicitaciones por completar el curso. Tu credencial digital está lista. Visita la actividad de certificado en el curso para reclamarla escaneando el código QR con tu Blerify Wallet.';

// Capabilities.
$string['blerify:addinstance'] = 'Agregar una nueva actividad de Certificado Blerify';
$string['blerify:view'] = 'Ver Certificado Blerify';
$string['blerify:manage'] = 'Gestionar Certificados Blerify';

// Security.
$string['usernotenrolled'] = 'El usuario especificado no está inscrito en este curso.';

// Configuracion del certificado en el formulario de la actividad.
$string['certificatename'] = 'Nombre del certificado';
$string['certificatename_help'] = 'El nombre que los estudiantes veran para este certificado en el curso.';
$string['templatetoissue'] = 'Plantilla a emitir';
$string['templatetoissue_help'] = 'La plantilla de credencial de Blerify con la que se emite este certificado. La lista proviene del proyecto configurado para este curso.';
$string['passgrade'] = 'Nota minima para emitir (%)';
$string['passgrade_help'] = 'La nota mínima requerida cuando Moodle marca el curso como finalizado.';

// Vista del estudiante.
$string['certificate_not_yet'] = 'Tu certificado aun no esta disponible.';
$string['certificate_threshold'] = 'Necesitas una nota del curso de al menos';
$string['certificate_current_grade'] = 'Tu nota actual:';
$string['pdf_download'] = 'Descargar PDF';
$string['pdf_open'] = 'Abrir el PDF';
$string['pdf_inline_unavailable'] = 'Tu navegador no puede mostrar el PDF aqui.';
$string['claim_btn'] = 'Reclamar mi credencial';
$string['claim_scan_prompt'] = 'Escanea este codigo con la billetera Blerify para reclamar tu credencial.';
$string['claim_or_open_link'] = 'Ya estas en tu celular?';
$string['claim_open_wallet'] = 'Abrir en la billetera';

// Estados de la credencial.
$string['status_issuing'] = 'Emitiendo';
$string['status_issued'] = 'Emitida';
$string['status_claimed'] = 'Reclamada';
$string['error_detail'] = 'Detalle del error';

// Errores.
$string['error_no_templates'] = 'El proyecto configurado para este curso no tiene plantillas de credencial disponibles.';
$string['error_templates_unavailable'] = 'No se pudieron cargar las plantillas de credencial. Revisa la configuracion de la cuenta de servicio de Blerify.';
$string['error_passgrade_range'] = 'La nota minima debe estar entre 0 y 100.';
$string['error_credential_not_ready'] = 'Esta credencial aun no esta disponible.';

$string['privacy:metadata:blerify_credentials:code'] = 'El codigo de reclamo usado para construir el enlace de la billetera';
$string['privacy:metadata:blerify_credentials:timecreated'] = 'La fecha en que se creo el registro de la credencial';
$string['privacy:metadata:blerify_api'] = 'Datos enviados a la plataforma Blerify para emitir una credencial';
$string['privacy:metadata:blerify_api:email'] = 'El correo al que se emite la credencial';
$string['privacy:metadata:blerify_api:fullname'] = 'El nombre que se imprime en la credencial';

$string['setting_project_id'] = 'ID de proyecto';
$string['setting_project_id_desc'] = 'Proyecto de Blerify bajo el que se emiten las credenciales. Todos los cursos usan este proyecto, salvo que se configure una excepcion por curso. Lo encuentras en la URL del panel de Blerify: /projects/<PROJECT-ID>/emission';
$string['error_no_project_id'] = 'No hay ningun proyecto de Blerify configurado. Pide al administrador que indique el ID de proyecto en los ajustes del plugin.';
