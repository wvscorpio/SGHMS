<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT_PATH', dirname(__DIR__));
define('DATA_PATH', ROOT_PATH . '/data');
define('APP_PATH', ROOT_PATH . '/application');
define('PRES_PATH', ROOT_PATH . '/presentation');

require_once DATA_PATH . '/db/dbcon.php';
require_once APP_PATH . '/auth.php';
require_once DATA_PATH . '/UserModel.php';
require_once DATA_PATH . '/PatientModel.php';
require_once DATA_PATH . '/DoctorModel.php';
require_once DATA_PATH . '/AppointmentModel.php';
require_once DATA_PATH . '/PrescriptionModel.php';
require_once DATA_PATH . '/NotificationModel.php';
require_once APP_PATH . '/AuthController.php';
require_once APP_PATH . '/AppointmentController.php';
require_once APP_PATH . '/PrescriptionController.php';
require_once APP_PATH . '/NotificationController.php';

function css_path(string $file): string
{
    return '../../presentation/css/' . $file;
}

function pres_web_root(): string
{
    return site_base() . '/presentation';
}

function site_base(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#^(/[^/]+)#', $script, $matches)) {
        return $matches[1];
    }

    return '/SGHMS';
}

function pres_asset(string $path): string
{
    $url = pres_web_root() . '/' . ltrim(str_replace('\\', '/', $path), '/');
    return ($url[0] === '/') ? $url : '/' . $url;
}

function pres_asset_version(string $path): string
{
    $file = PRES_PATH . '/' . ltrim(str_replace('\\', '/', $path), '/');
    return file_exists($file) ? (string) filemtime($file) : (string) time();
}

function pres_url(string $path): string
{
    return '../../presentation/' . ltrim($path, '/');
}

function app_url(string $path): string
{
    return '../../application/' . ltrim($path, '/');
}

function formatDoctorDisplayName(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'Dr. Doctor';
    }
    if (preg_match('/^dr\.?\s+/i', $name)) {
        return $name;
    }

    return 'Dr. ' . $name;
}

function doctorCanPrescribeForAppointment(?array $appointment, string $doctorID): bool
{
    return $appointment
        && ($appointment['doctorID'] ?? '') === $doctorID
        && ($appointment['status'] ?? '') === 'Confirmed';
}

function normalizeContactNumber(string $value): string
{
    return preg_replace('/\D+/', '', $value);
}

function isValidContactNumber(string $value): bool
{
    $digits = normalizeContactNumber($value);

    return (bool) preg_match('/^0\d{9,10}$/', $digits);
}

const RX_STATUS_PENDING = 'Pending Preparation';
const RX_STATUS_READY = 'Ready for Collection';
const RX_STATUS_COLLECTED = 'Collected';

function normalizePrescriptionStatus(string $status): string
{
    if ($status === 'Active' || $status === 'Issued') {
        return RX_STATUS_PENDING;
    }

    return $status;
}

function prescriptionStatusLabel(string $status, string $audience = 'doctor'): string
{
    $status = normalizePrescriptionStatus($status);

    return match ($status) {
        RX_STATUS_PENDING => match ($audience) {
            'patient' => 'Being Prepared',
            'staff' => 'Awaiting Preparation',
            default => 'Sent to Pharmacy',
        },
        RX_STATUS_READY => 'Ready for Collection',
        RX_STATUS_COLLECTED => 'Collected',
        default => $status,
    };
}

function patientCanViewPrescriptionDetails(string $status): bool
{
    $status = normalizePrescriptionStatus($status);

    return in_array($status, [RX_STATUS_READY, RX_STATUS_COLLECTED], true);
}

function parsePrescriptionDiagnosis(string $instructions): string
{
    if (preg_match('/Diagnosis\/Notes:\s*(.+)$/i', $instructions, $matches)) {
        return trim($matches[1]);
    }

    return '—';
}

function parsePrescriptionFrequency(string $instructions): string
{
    if (preg_match('/Frequency:\s*([^.]+)\./i', $instructions, $matches)) {
        return trim($matches[1]);
    }

    return '—';
}

function parsePrescriptionInstructions(string $instructions): string
{
    $text = preg_replace('/Frequency:\s*[^.]+\.\s*/i', '', $instructions);
    $text = preg_replace('/\s*Diagnosis\/Notes:.*$/i', '', $text ?? '');
    $text = trim($text);

    return $text !== '' ? $text : '—';
}

function buildPatientPrescriptionPayload(array $rx, ?array $appointment = null): array
{
    $instructions = $rx['instructions'] ?? '';
    $status = normalizePrescriptionStatus($rx['status'] ?? RX_STATUS_PENDING);
    $diagnosedDate = $appointment['diagnosedDate'] ?? $rx['diagnosedDate'] ?? null;

    return [
        'prescriptionID' => $rx['prescriptionID'],
        'appointmentID' => $rx['appointmentID'] ?? ($appointment['appointmentID'] ?? '—'),
        'patientName' => $rx['patientName'] ?? '—',
        'doctor' => formatDoctorDisplayName($rx['doctorName'] ?? 'Doctor'),
        'medicine' => $rx['medicineName'],
        'dosage' => $rx['dosage'],
        'frequency' => parsePrescriptionFrequency($instructions),
        'duration' => $rx['duration'],
        'instructions' => parsePrescriptionInstructions($instructions),
        'diagnosis' => parsePrescriptionDiagnosis($instructions),
        'status' => prescriptionStatusLabel($status, 'patient'),
        'rawStatus' => $status,
        'canViewDetails' => patientCanViewPrescriptionDetails($status),
        'diagnosedDate' => $diagnosedDate ? date('d M Y', strtotime($diagnosedDate)) : '—',
    ];
}
