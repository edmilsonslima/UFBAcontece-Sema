<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\StoreEventoRequest;
use App\Http\Controllers\Controller;
use App\Categoria;
use App\Departamento;
use App\Curso;
use App\Unidade;
use App\Evento;

use EasyRdf_Format;
use EasyRdf_Graph;
use EasyRdf_Namespace;
use App\User as Usuario;
use DB;
use Response;
use Auth;
use Image;
use PhpParser\Node\Stmt\Echo_;

class BackendController extends Controller
{
    public function index()
    {
        $cursos = Curso::lists('titulo', 'id');
        $unidades = Unidade::lists('titulo', 'id');
        $eventos = Evento::where('ativo', 'Y')->with('usuario', 'comentarios.usuario')->orderBy('created_at', 'desc')->get();
        return view('backend.dashboard.index', compact('cursos','unidades', 'eventos'));
    }

    public function eventosCalendario()
    {
      $eventos = Evento::all()->where('ativo', 'Y');
      return Response::json($eventos);
    }

    public function eventos()
    {
      return view('backend.dashboard.eventos');
    }

    public function detalharEvento($eventoId)
    {
      $evento = Evento::findOrFail($eventoId);
      $publicacoes = $evento->publicacoes()->orderby('created_at', 'desc')->get();
      return view('backend.evento.detalhe', compact('evento', 'publicacoes'));
    }

    public function participantesByCurso($eventoId)
    {
      $participantes = DB::table('eventos_participantes')
            ->join('usuarios', 'usuarios.id', '=', 'eventos_participantes.usuario_id')
            ->join('cursos', 'cursos.id', '=', 'usuarios.curso_id')
            ->select(DB::raw('count(*) as numero'), 'cursos.titulo')
            ->where('eventos_participantes.evento_id', $eventoId)
            ->groupBy('cursos.id')
            ->get();
            return $participantes;

    }

    public function eventosPresente()
    {
      $eventos = DB::table('eventos')
      ->join('eventos_participantes', 'eventos_participantes.evento_id', '=', 'eventos.id')
      ->select('eventos.id', 'eventos.titulo', 'eventos.endereco', 'eventos.imagem')
      ->where('eventos_participantes.usuario_id', Auth::user()->id)
      ->get();
      //var_dump($eventos);
      return view('backend.dashboard.eventosPresente', compact('eventos'));
    }

    public function eventosCriado()
    {
      $eventos = Evento::with('categoria', 'departamento')->where('usuario_id', Auth::user()->id)->paginate(15);
      return view('backend.dashboard.eventosCriado', compact('eventos'));
    }

    public function cadastrarEvento()
    {
      $categorias = Categoria::lists('titulo', 'id');
      $departamentos = Departamento::lists('titulo', 'id');
      return view('backend.dashboard.criarEvento', compact('categorias','departamentos'));
    }

    public function storeEventoUsuario(StoreEventoRequest $request)
    {
      //Trata data_inicio
      //$dataInicioFormatada = Carbon::createFromFormat('d/m/Y', $request->get('data_inicio'))->format('yyyy-mm-dd');
      $dataInicioFormatada = str_replace('/', '-', $request->get('data_inicio'));
      $dataInicioFormatada = date('Y-m-d', strtotime($dataInicioFormatada));

      //Trata data_inicio
      //$dataFimFormatada = Carbon::createFromFormat('d/m/Y', $request->get('data_fim'))->format('yyyy-mm-dd');
      $dataFimFormatada = str_replace('/', '-', $request->get('data_fim'));
      $dataFimFormatada = date('Y-m-d', strtotime($dataFimFormatada));

      $evento = new Evento(array(
        'categoria_id' => $request->get('categoria_id'),
        'departamento_id' => $request->get('departamento_id'),
        'titulo' => $request->get('titulo'),
        'descricao'  => $request->get('descricao'),
        'data_inicio'  => $dataInicioFormatada,
        'data_fim'  => $dataFimFormatada,
        'endereco'  => $request->get('endereco'),
        'ativo'  => $request->get('ativo'),
        'usuario_id'  => Auth::user()->id
      ));

      if(!$evento->save()){
        return redirect()->back()
        ->with('status', 'Erro ao cadastrar Evento.');
      }

      //Trata e salva a imagem nova
      if ($request->hasFile('imagem')) {
        $file = $request->file('imagem');
        $filename  = time() . $evento->id .'.' . $file->getClientOriginalExtension();
        $path = public_path('uploadsDoUsuario/' . $filename);
        Image::make($file->getRealPath())->fit('607','190')->save($path);
        $evento->imagem = $filename;
      }

      $evento->save();

      return redirect()->action('BackendController@eventosCriado')
      ->with('status', 'Evento '. $evento->titulo .' cadastrado.');
    }

    public function excluirEvento($id)
    {
      $evento = Evento::findOrFail($id);

      //Deleta a imagem se houver
      if(!empty($evento->imagem)){
          unlink('uploadsDoUsuario'.DIRECTORY_SEPARATOR.$evento->imagem);
      }

      if($evento->delete()){
        return redirect()->action('BackendController@eventosCriado')
        ->with('status', 'Evento '. $evento->titulo.' exclu√≠do.');
      }
    }

        public function rdfEvento($id)
        {
            $evento = Evento::findOrFail($id);
            
            //header('Content-Type: application/rdf+xml');
            //$rdf = EasyRdf_Graph::newAndLoad('http://www.semanticweb.org/luk3t4/ontologies/2018/1/untitled-ontology-11');
            
            
            // Prefixos/namespaces
            EasyRdf_Namespace::set('sem','http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:');
            EasyRdf_Namespace::set('rdf','http://www.w3.org/1999/02/22-rdf-syntax-ns#');
            EasyRdf_Namespace::set('rdfs','http://www.w3.org/2000/01/rdf-schema#');
            
            //Instanciando o grafo
            $graph = new EasyRdf_Graph();
            
            //  * RECURSOS DO EVENTO
            
            // Add recurso evento ao grafico
            $event = $graph->resource('http://localhost:8000/painel/evento/' .$evento->id, 'http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Event');
            $event->addLiteral('http://www.w3.org/2000/01/rdf-schema#label', $evento->titulo);
            
            //  * ADCIONANDO NOVO NO *
            
            //pessoa novo nÛ do grafo do tipo ator
            $actor = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Actor');
            // novo nÛ do grafo local(classe)
            $place = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Place');
            // novo nÛ para Tempo
            $time = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Time');
            // novo nÛ para eventType
            //$eventType = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:eventType');
            // novo nÛ para core
            //$core = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Core');
            
            //  * RECURSOS EVENTTYPE *
            
            //  * RECURSOS DA ATOR *
            
            $usuario = Usuario::findOrFail($evento->usuario->id);
            // label da pessoa.
            //$actor->addLiteral('http://xmlns.com/foaf/0.1/name', $usuario->nome);
            //$actor->addLiteral('http://www.w3.org/2000/01/rdf-schema#label', 'http://localhost:8000/painel/perfil/'.(string)$evento->usuario->id );//'*URI do usuario criador do evento');
            $listaParticipante =$evento->participantes ;
            foreach ($listaParticipante as $i => $value) {
                $actor->add('http://www.w3.org/2000/01/rdf-schema#label',Usuario::findOrFail($listaParticipante[$i]->usuario_id)->nome);
            }
            
            //  * RECURSOS DO LOCAL**
            
            // label da local.
            $place->addLiteral('http://www.w3.org/2000/01/rdf-schema#label', $evento->endereco);
            
            //  * RECURSOS DO TEMPO
            
            // label do tempo.
            $time->addLiteral('http://www.w3.org/2000/01/rdf-schema#',  $evento->data_inicio .'---' .$evento->data_fim);
            //$time->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:timeType',$evento);
            
            //  * RECURSOS CORE *
            
            //  * CONCATENANDO OS N”S *
            
            // propriedade foiAtor(evento,pessoa)
            $event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:hasActor',$actor);
            // propriedade foiLocal(evento,local)
            $event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:hasPlace',$place);
            // propriedade foiHora(evento,hora)
            $event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:hasTime',$time);
            
          
            
            
           
            
            
            //  * IMPRIMINDO NA TELA *
            //echo $evento->titulo;
            print $graph->dump();
            //print $graph->serialise('rdfxml');
            
            
            
           
        }
    public function rdfEventodown ($id)
    {
      
        $evento = Evento::findOrFail($id);
        
        //header('Content-Type: application/rdf+xml');
        //$rdf = EasyRdf_Graph::newAndLoad('http://www.semanticweb.org/luk3t4/ontologies/2018/1/untitled-ontology-11');
        
        $listaParticipante =$evento->participantes ;
        //$graph = new EasyRdf_Graph();
        $arr= '' ;
        foreach ($listaParticipante as $i => $value) {
            $arr =  $arr .' ,' .Usuario::findOrFail($listaParticipante[$i]->usuario_id)->nome
            
           .' ,' .Usuario::findOrFail($listaParticipante[$i]->usuario_id)->nome;
        }
        
        
       // echo ($actor);
        // Prefixos/namespaces
        $text = 'PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:>' ."\n"  ;
        $text =$text . 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>' ."\n" ;
        $text =$text . 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>' ."\n";
        //$text =$text .'<res:solution rdf:nodeID=' .'"' .'r10' .'"' .'>' . "\n" ;
        $text =$text .'<rdf:Description ' .'http://localhost:8000/painel/evento/' .$evento->id .'>' ."\n" ;
        
        $text = $text .'<rdf:type ' .'  sem:Event>' ."\n";
        
        $text = $text .'<rdf:label' .'"'  .$evento->titulo .'"'  . '>' ."\n";
        
        $text = $text .'<foaf:name' .'"'  .$evento->Place .'"' .'sem:Place' . '>' ."\n";
        
        
        //$text = $text . <sem:Actor
        
        $text =$text .'</rdf:Description>' ."\n";
        
       
        /*
        
        //$text =$text . '<!-- place properties --> '."\n";
        $text =$text . '<owl:ObjectProperty rdf:about=' .'"'.' &sem;hasPlace' .'"' .' rdfs:label=' .'"'.' has Place' .'"' .'>' ."\n";
        */
       // $text =$text . '  <rdfs:Place>' .$evento->endereco  .'</rdfs:Place>'."\n";
       /* $text =$text . '  <rdfs:range rdf:resource=' .'"' .'&sem;Place' .'"' .'/>' ."\n";
        $text =$text . '  <rdfs:subPropertyOf rdf:resource=' .'"'.' &sem;eventProperty' .'"' .'/>'."\n";
        $text =$text . '  <skos:narrowMatch rdf:resource=' .'"' .'&lode;inSpace' .'"' .'/>'."\n";
        $text =$text . '  <skos:exactMatch rdf:resource=' .'"'.' &cs;place' .'"' .'/>'."\n";
        $text =$text . '</owl:ObjectProperty>'."\n";
        $text =$text . '<rdf:Description rdf:about=' .'"' .'&lode;inSpace' .'"' .'><rdfs:isDefinedBy rdf:resource=' .'"' .'&lode;' .'"' .' /></rdf:Description>'."\n";
        $text =$text . '<rdf:Description rdf:about=' .'"' .'&cs;place' .'"' .' ><rdfs:isDefinedBy rdf:resource=' .'"' .'&cs;' .'"' .'/></rdf:Description>'."\n";
     
        
       // $text =$text . '<!-- actor properties -->' . "\n";
        $text =$text . '<owl:ObjectProperty rdf:about=' .'"' .'&sem;hasActor' .'"' .' rdfs:label=' .'"' .'has Actor' .'"' .'>' . "\n";
        $text =$text . ' <rdfs:comment>' . $arr  .'</rdfs:comment>' . "\n";
        $text =$text . '  <rdfs:range rdf:resource=' .'"' .'&sem;Actor' .'"' .'/>' . "\n";
        $text =$text . '  <rdfs:subPropertyOf rdf:resource=' .'"' .'&sem;eventProperty' .'"' .'/>' . "\n";
        $text =$text . '  <skos:exactMatch rdf:resource=' .'"' .'&lode;involved' .'"' .'/>' . "\n";
        $text =$text . '  <skos:narrowMatch rdf:resource=' .'"' .'&cs;agent' .'"' .'/>' . "\n";
        $text =$text . '</owl:ObjectProperty>' . "\n";
        $text =$text . '<rdf:Description rdf:about=' .'"' .'&lode;involved' .'"' .'><rdfs:isDefinedBy rdf:resource=' .'"' .'&lode;' .'"' .'/></rdf:Description>' . "\n";
        $text =$text . '<rdf:Description rdf:about=' .'"' .'&cs;agent' .'"' .'><rdfs:isDefinedBy rdf:resource=' .'"' .'&cs;' .'"' .'/></rdf:Description>' . "\n";
        */
        /*
        $event = $graph->resource('http://localhost:8000/painel/evento/' .$evento->id, 'http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Event');
        $event->addLiteral('http://www.w3.org/2000/01/rdf-schema#label', $evento->titulo);
        
        //  * ADCIONANDO NOVO NO *
        //pessoa novo nÛ do grafo do tipo ator
        $actor = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Actor');
        // novo nÛ do grafo local(classe)
        $place = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Place');
        // novo nÛ para Tempo
        $time = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Time');
        // novo nÛ para eventType
        //$eventType = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:eventType');
        // novo nÛ para core
        //$core = $graph->newBNode('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:Core');
        
        //  * RECURSOS EVENTTYPE *
        
        //  * RECURSOS DA ATOR *
        
        $usuario = Usuario::findOrFail($evento->usuario->id);
        // label da pessoa.
        //$actor->addLiteral('http://xmlns.com/foaf/0.1/name', $usuario->nome);
        //$actor->addLiteral('http://www.w3.org/2000/01/rdf-schema#label', 'http://localhost:8000/painel/perfil/'.(string)$evento->usuario->id );//'*URI do usuario criador do evento');
        $listaParticipante =$evento->participantes ;
        foreach ($listaParticipante as $i => $value) {
            $actor->add('http://www.w3.org/2000/01/rdf-schema#label',Usuario::findOrFail($listaParticipante[$i]->usuario_id)->nome);
        }
        
        //  * RECURSOS DO LOCAL**
        
        // label da local.
        $place->addLiteral('http://www.w3.org/2000/01/rdf-schema#label', $evento->endereco);
        
        //  * RECURSOS DO TEMPO
        
        // label do tempo.
        //*** $time->addLiteral('http://www.w3.org/2000/01/rdf-schema#',  getValue($evento));
        //$time->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:timeType',$evento);
        
        //  * RECURSOS CORE *
        
        //  * CONCATENANDO OS N”S *
        
        // propriedade foiAtor(evento,pessoa)
        $event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:hasActor',$actor);
        // propriedade foiLocal(evento,local)
        $event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:hasPlace',$place);
        // propriedade foiHora(evento,hora)
        $event->add('http://semanticweb.cs.vu.nl/2009/11/sem/semdoc.html#sem:hasTime',$time);
        
        
        
        */
        
        
        
        
        $name = $evento->id .'evento872.rdf';
        //$text = $graph->dump('rdf');
        
        $file = fopen($name, 'a');
        fwrite($file, $text);
        fclose($file);
    
        
        $myFile = public_path($name);
        $headers = ['Content-Type: application/pdf'];
        $newName = $name;//'itsolutionstuff-pdf-file-'.time().'.rdf';
        return response()->download($myFile, $newName, $headers);
        
          
        
        
        
        {
            //return redirect()->action('BackendController@eventosCriado')
            // ->with('status', 'Evento '. $evento->titulo.' exclu√≠do.');
        }
    }
    
}
