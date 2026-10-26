@extends('errors.layout')
@section('codigo', '403')
@section('titulo', 'Acceso no autorizado')
@section('mensaje', $exception?->getMessage() && $exception->getMessage() !== 'This action is unauthorized.' ? $exception->getMessage() : 'Su perfil de usuario no tiene permiso para realizar esta acción. Si considera que se trata de un error, comuníquese con el administrador del sistema.')
