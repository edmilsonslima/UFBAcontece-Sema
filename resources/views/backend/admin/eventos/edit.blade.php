@extends('backend.layout.master')

@section('conteudo')
<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>
    Eventos
    <small>Manutenção de Eventos</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="#"><i class="fa fa-dashboard"></i> Início</a></li>
    <li ><a href="#">Eventos</a></li>
    <li class="active"><a href="#">Editar evento - {{ $evento->titulo }}</a></li>
  </ol>
</section>

<!-- Main content -->
<section class="content">
  @if (count($errors) > 0)
      <div class="alert alert-danger">
          <ul>
              @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
              @endforeach
          </ul>
      </div>
  @endif
    <div class="box box-danger">
      <div class="box-header">
        <h3 class="box-title">Editar evento - {{ $evento->titulo }}</h3>
      </div>
      <div class="box-body">
        <form method="post" action="{{action('Backend\EventosController@update', $evento->id)}}" enctype="multipart/form-data">
          {!! csrf_field() !!}
        <div class="form-group">
          <label>Título</label>
          <div class="input-group col-md-6">
            <div class="input-group-addon">
              <i class="fa fa-font"></i>
            </div>
            <input type="text" name="titulo" class="form-control" value="{{ $evento->titulo }}" >
          </div> <!-- /.input group -->
          <label>Categoria:</label>
          <div class="input-group col-md-4">
          <select class="form-control" name="categoria_id" value="{{ $evento->categoria_id }}">
            @foreach($categorias as $id => $categoria)
            <option value="{{$id}}" {{ $id == $evento->categoria_id ? 'selected' : ''}}>{{$categoria}}</option>
            @endforeach
          </select>
        </div>
          <label>Departamento:</label>
          <div class="input-group col-md-4">
          <select class="form-control" name="departamento_id" value="{{ $evento->departamento_id }}">
            @foreach($departamentos as $id => $departamento)
            <option value="{{$id}}"  {{ $id == $evento->departamento_id ? 'selected' : ''}} >{{$departamento}}</option>
            @endforeach
          </select>
        </div>
          <label>Data de Início:</label>
          <div class="input-group col-md-3">
            <div class="input-group-addon">
              <i class="fa fa-calendar"></i>
            </div>
            <input type="text" id="datemask" name="data_inicio" class="form-control" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask value="{{ $evento->present()->dataInicioFormatada }}">
          </div> <!-- /.input group -->
            <label>Data de Fim:</label>
            <div class="input-group col-md-3">
              <div class="input-group-addon">
                <i class="fa fa-calendar"></i>
              </div>
              <input type="text" id="datemask" name="data_fim" class="form-control" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask value="{{ $evento->present()->dataFimFormatada }}">
          </div> <!-- /.input group -->
          <label>Endereço:</label>
          <div class="input-group col-md-3">
            <div class="input-group-addon">
              <i class="fa fa-map"></i>
            </div>
            <input type="text" name="endereco" class="form-control" value="{{ $evento->endereco }}">
        </div> <!-- /.input group -->
          <label>Descrição do Evento:</label>
          <div class="input-group col-md-12">
            <textarea id="editor1" name="descricao" rows="10" cols="80" style="visibility: hidden; display: none;">
              {{ $evento->descricao }}
            </textarea>
        </div> <!-- /.input group -->
        </div>
        <div class="form-group">
          <label>Status:</label>
          <div class="radio">
            <label>
              <input type="radio" name="ativo" id="optionsRadios1" value="Y" checked >
                Ativo
              </label>
          </div>
          <div class="radio">
            <label>
              <input type="radio" name="ativo" id="optionsRadios1" value="N" >
                Inativo
            </label>
          </div>
        </div>
        <div class="form-group">
          <label for="imagem">Imagem principal</label>
            <input type="file" name="imagem" id="imagem" value="{{ $evento->imagem }}">
            <p class="help-block">O tamanho da imagem não deve exceder 1MB.</p>
        </div>
        <div class="form-group">
            @if(!empty($evento->imagem))
            <img src="{{ asset('uploadsDoUsuario/')}}/{{$evento->imagem}}" ></img>
            @endif
        </div>
      </div><!-- /.box-body -->
      <div class="box-footer">
        <button type="submit" class="btn btn-success pull-left">Atualizar</button>
        <a href="{{ action('Backend\EventosController@index') }}" class="btn btn-danger pull-right">Cancelar</a>
      </form>
      </div>
    </div>
</section><!-- /.content -->
@endsection

@section('scriptsExtra')
<script src="{{ asset('plugins/input-mask/jquery.inputmask.js') }}"></script>
<script src="{{ asset('plugins/input-mask/jquery.inputmask.date.extensions.js') }}"></script>
<script src="{{ asset('plugins/input-mask/jquery.inputmask.extensions.js') }}"></script>
<script src="{{ asset('plugins/ckeditor/ckeditor.js') }}"></script>
<script>
  $(function () {
    CKEDITOR.replace('editor1');
  });
</script>
<script>

      $(function () {

        //Datemask dd/mm/yyyy
        $("#datemask").inputmask("dd/mm/yyyy", {"placeholder": "dd/mm/yyyy"});
        //Money Euro
        $("[data-mask]").inputmask();
      });

</script>
@endsection
