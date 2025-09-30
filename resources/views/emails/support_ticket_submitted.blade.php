<!DOCTYPE html>
<html>
  <body>
    <h2>New Support Ticket</h2>
    <p><strong>User:</strong> {{ $user->name }} ({{ $user->email }})</p>
    <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
    <p><strong>Priority:</strong> {{ $ticket->priority }}</p>
    <p><strong>Message:</strong></p>
    <pre style="white-space:pre-wrap">{{ $ticket->message }}</pre>
    <p>Ticket ID: {{ $ticket->id }}</p>
  </body>
</html>
