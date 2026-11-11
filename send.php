<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false]);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  $data = $_POST;
}

function field($data, $key, $max) {
  $value = trim((string) ($data[$key] ?? ''));
  $value = str_replace(array("\r", "\n"), ' ', $value);
  if (strlen($value) > $max) {
    $value = substr($value, 0, $max);
  }
  return $value;
}

$name = field($data, 'name', 120);
$phone = field($data, 'phone', 40);
$email = field($data, 'email', 120);
$topic = field($data, 'topic', 160);
$message = trim((string) ($data['message'] ?? ''));
$message = str_replace("\r\n", "\n", $message);
if (strlen($message) > 4000) {
  $message = substr($message, 0, 4000);
}

if ($name === '' || $phone === '' || $topic === '' || $message === '') {
  http_response_code(400);
  echo json_encode(['success' => false]);
  exit;
}

$to = 'hornet105@wp.pl';
$subject = 'Zapytanie: ' . $name . ' - ' . $topic;
$body = "Imię i nazwisko: {$name}\nTelefon: {$phone}\n";
$reply = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
if ($reply !== '') {
  $body .= "E-mail: {$reply}\n";
}
$body .= "Temat: {$topic}\n\n{$message}\n";

$headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
if ($reply !== '') {
  $headers .= 'Reply-To: ' . $reply . "\r\n";
}

$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$sent = mail($to, $encodedSubject, $body, $headers);

if (!$sent) {
  http_response_code(500);
  echo json_encode(['success' => false]);
  exit;
}

echo json_encode(['success' => true]);
