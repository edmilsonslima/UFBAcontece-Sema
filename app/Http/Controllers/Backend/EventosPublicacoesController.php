<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Auth;
use App\EventoPublicacao;

class EventosPublicacoesController extends Controller
{

    public function store(Request $request, $eventoId)
    {
      $publicacao = new EventoPublicacao();
      $publicacao->usuario_id = Auth::user()->id;
      $publicacao->evento_id  = $eventoId;
      $publicacao->texto      = $request->input('texto');
      $publicacao->save();
      return redirect()->action('BackendController@detalharEvento', $eventoId)
      ->with('status', 'Publicação concluída.')
      ->with('aba', 'publicacoes');
    }

    public function destroy($publicacaoId)
    {
      $publicacao = EventoPublicacao::findOrFail($publicacaoId);
      if($publicacao->delete()){
        return redirect()->action('BackendController@detalharEvento', $publicacao->evento_id)
        ->with('status', 'Publicação excluída.')
        ->with('aba', 'publicacoes');
      }
    }

}
