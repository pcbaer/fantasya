@extends('layout')

@section('headline')
	Datenschutz
@stop

@section('text')
	<h3>Fantasya liebt Datenschutz.</h3>
	<p>Und deswegen verzichten wir darauf, unnötig personenbezogene Daten zu erheben. Nur die allernötigsten
		persönlichen Daten speichern wir über unsere Nutzer und Mitspieler:</p>
	<ol>
		<li>Benutzername</li>
		<li>Passwort (verschlüsselt)</li>
		<li>E-Mail-Adresse</li>
		<li>Name und Beschreibung der gespielten Partei</li>
	</ol>
	<p>Wir protokollieren außerdem alle Aufrufe einer Fantasya-Seite und speichern die zugehörige IP-Adresse und
		Browserinformationen für einen Zeitraum von maximal sieben Tagen.</p>

	<h3>Cookies</h3>
	<p>Wie nahezu alle anderen Angebote in den Weiten des Internet benutzt Fantasya sogenannte <i>Cookies</i> für die
		Identifizierung der Benutzer. Dies sind kleine Textdateien, die auf dem Computer des Benutzers gespeichert
		werden und über den Browser gelöscht werden können.</p>

	<h3>Datenweitergabe</h3>
	<p>Fantasya ist ein Multiplayer-Spiel per E-Mail. Das bedeutet, wenn sich zwei Spieler im Spiel treffen, werden ihre
		Parteinamen und E-Mail-Adressen automatisch gegenseitig ausgetauscht, damit die Spieler in Rollenspielform
		kommunizieren können. Wir geben also diese personenbezogenen Daten an Dritte weiter.</p>

	<h3>Weitere Informationen</h3>
	<p>Wir haben uns bemüht, diese Datenschutzerklärung möglichst kurz zu halten. Weitere ausführliche Informationen
		zum Datenschutz in der Europäischen Union und Deinen Rechten findest Du hier:</p>
	<p><a href="https://deinedatendeinerechte.de/" target="_blank">Deine Daten Deine Rechte</a></p>

	<h3>Einwilligung</h3>
	<p>Um an Fantasya teilzunehmen, musst Du diese Bedingungen akzeptieren.</p>
	<p>Du kannst Deine Zustimmung jederzeit widerrufen, indem Du in Deinem Browser alle Cookies von fantasya.de löschst.
		Du musst dann Deine Zustimmung erneut erteilen, um das Spiel fortzusetzen.</p>
	<p>Auf Anfrage löschen wir alle Deine gespeicherten Daten, und Du scheidest aus dem Spiel aus. Auf die bereits
		weitergegebenen Daten haben wir natürlich keinen Einfluss.</p>
	@if ($showForm)
		{{Form::open(array('action' => 'FantasyaController@privacy'))}}
			<?php echo Form::checkbox('accept', '1'); ?>
			<?php echo Form::label('accept', 'Ich möchte an Fantasya teilnehmen und akzeptiere diese Bedingungen.'); ?><br>
			<?php echo Form::submit('Zustimmen'); ?><br>
		{{Form::close()}}
	@else
		<p>Du hast bereits Deine Zustimmung gegeben.</p>
	@endif
@stop
