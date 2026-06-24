
<!DOCTYPE html>
<html >
    <head>
    <meta charset="UTF-8">
    <title>Nouvelle réclamation reçue</title>
    </head>
    <body>
    
    <h2>Nouvelle réclamation reçue</h2>
    <p><strong>Nom :</strong> {{ $ticket->nom }}</p>
    <p><strong>Email :</strong> {{ $ticket->email }}</p>
    <p><strong>Type :</strong> {{ $ticket->type }}</p>
    @if($ticket->demande_id)
    <p><strong>ID Demande :</strong> {{ $ticket->demande_id }}</p>
    @endif
    <p><strong>Description :</strong></p>
    <p>{{ $ticket->description }}</p>
    @if($ticket->file_path)
    <p>📎 Une pièce jointe est incluse avec cette réclamation.</p>
    @endif
    <p>—<br>L’équipe Aktly Lite</p>
    </body>
</html>
