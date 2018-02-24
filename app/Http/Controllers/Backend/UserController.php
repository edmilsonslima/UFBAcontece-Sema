<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use EasyRdf_Graph;
use EasyRdf_Namespace;
use Image;

use App\User;

use Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //dd($request->input('termoPesquisa'));
        if(!empty($request->input('termoPesquisa')))
        {
            $usuarios = User::where('nome', 'LIKE', '%'.$request->input('termoPesquisa').'%')->orderBy('nome')->paginate(10);
        }else{
            $usuarios = User::orderBy('nome')->paginate(10);
        }
        return view('backend.usuarios.index', compact('usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $usuario = User::findOrFail(Auth::user()->id);

        $usuario->nome = $request->get('nome');
        $usuario->curso_id = $request->get('curso_id');
        $usuario->unidade_id = $request->get('unidade_id');
        $usuario->apresentacao = $request->get('apresentacao');

        //Trata e salva a imagem nova
        if ($request->hasFile('foto')) {

          //Deleta a imagem antiga se houver
          if(!empty($usuario->foto)){
              unlink('uploadsDoUsuario/perfil'.DIRECTORY_SEPARATOR.$usuario->foto);
          }

          $file = $request->file('foto');
          $filename  = time() . $usuario->id .'.' . $file->getClientOriginalExtension();
          $path = public_path('uploadsDoUsuario/perfil/' . $filename);
          Image::make($file->getRealPath())->resize('200','200')->save($path);
          $usuario->foto = $filename;
        }

        if($usuario->save()){
          //return redirect()->to(app('url')->previous(). '#settings');
          return redirect()->action('BackendController@index')
          ->with('statusPerfil', 'Perfil atualizado.')
          ->with('aba', 'settings');
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function show($id){
        $usuario = User::findOrFail($id);
        return view('backend.perfil.index', compact('usuario'));
    }
    
    public function RDF($id){
       
        $usuario = User::findOrFail($id);
        
       
        
        
        
        EasyRdf_Namespace::set('dbc', 'http://dbpedia.org/page/Student/');
        
        //Instanciando o grafo
        $graph = new EasyRdf_Graph();
        
        //  **** RECURSOS DO EVENTO ***
        
        // cria um novo recurso no grafico, 1º parametro recurso,2º parametro valor da propriedade
        $event = $graph->resource('http://localhost:8000/painel/perfilRdf/' .$usuario->id, 'http://dbpedia.org/page/Student/#dbc:Students');
        //nome do evento(literal) do tipo foaf
        $event->addLiteral('http://xmlns.com/foaf/0.1/name', $usuario->nome);
        //tipo do evento sem:eventype
            //  $event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:eventType','*variavel da categoria*');
        
        //  **** ADCIONANDO NOVO NO ****
        
        //pessoa novo nó do grafo do tipo ator
             //$pessoa = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Actor');
        // novo nó do grafo local(classe)
                // $local = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Place');
                // $hora = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Time');
        
        //  **** RECURSOS DA PESSOA ***
        
        // label da pessoa.
            // $pessoa->addLiteral('rdfs:label', '*URI do usuario criador do evento');
        
        //  **** RECURSOS DO LOCAL***
        
        // label da local.
            // $local->addLiteral('rdfs:label', '*URI do local do evento');
        
        //  **** RECURSOS DO TEMPO ***
        
        // label do tempo.
                // $hora->addLiteral('rdfs:label', '*URI da data do evento');
                // $hora->add('vhttp://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:timeType','*Variavel do tempo do evento');
        
        //  **** CONCATENANDO OS NÓS ****
        
        // propriedade foiAtor(evento,pessoa)
                //$event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:hasActor', $pessoa);
        // propriedade foiLocal(evento,local)
            // $event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:hasPlace',$local);
        // propriedade foiHora(evento,hora)
            // $event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:hasTime',$hora);
        
        //  **** IMPRIMINDO NA TELA ****
        //echo $evento->titulo;
        print $graph->dump();
        //header('Content-Type: application/rdf+xml');
        //print $graph->serialise('rdfxml');
    }
}
