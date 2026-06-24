<html>
<body>
    @if($langue_user == 'nl')
        <h2>Welkom bij Aktly Lite!</h2>
        <p>Om uw e-mailadres te bevestigen, klikt u op de onderstaande link:</p>
        <a href="{{ $verificationUrl }}">Bevestig mijn e-mail</a>
        <p>Als u geen account heeft aangemaakt, negeer dan dit bericht.</p>
    @elseif($langue_user == 'fr')
    <h2>Bienvenue sur Aktly Lite !</h2>
    <p>Pour valider votre adresse email, veuillez cliquer sur le lien ci-dessous :</p>
    <a href="{{ $verificationUrl }}">Valider mon email</a>
    <p>Si vous n'avez pas créé de compte, ignorez ce message.</p>
    @endif
</body>
</html>
