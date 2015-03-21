@extends('layout')

@section('headline')
    Die Welten
@stop

@section('text')
    <h3>{{$game->name}}</h3>
    <p>{{$game->description}}</p>
    <p><strong>Auswertung: </strong>{{Weekday::translate($game->adddays)}}, 17 Uhr</p>
    <p>
        <strong>Letzter Zug: </strong>Runde {{$turn - 1}} vom {{Date::translate($game->lastzat)}}<br>
        <strong>Nächster Zug: </strong>Runde {{$turn}} am {{Date::translate($game->nextzat)}}
    </p>
@stop
