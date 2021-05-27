@extends('layouts.app')

@section('css')
    <style>
        #stocklink button {
            margin-bottom: 5px;
        }
    </style>
@endsection

@section('content')
    <section class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h1 class="mt-5 pt-3">Set Cookie for Parser</h1>
                @if ($message = Session::get('success'))
                    <div class="alert alert-success" role="alert">
                        {{ $message }}
                    </div>
                @endif
                {!! Form::open(array('method' => 'POST', 'files' => true, 'route' => ['cookies.update'], 'class' => 'form mt-3')) !!}
                    <div class="form-group">
                        {!! Form::label('parser', 'Select parser') !!}
                        {!! Form::select("name", array( 'sape' => 'Sape', 'miralinks' => 'Miralinks', 'gogetlinks' => 'Gogetlinks', 'rotapost' => 'Rotapost', 'prnews' => 'Prnews'), null, array('class' => 'form-control')) !!}
                        {!! ($errors->any()) ? '<span class="invalid-feedback" style="display: block">' . $errors->first('parser') . '</span>' : '' !!}
                    </div>
                        <div class="form-group">
                            {!! Form::textarea('cookie', null, array('class' => 'form-control')) !!}
                            {!! ($errors->any()) ? '<span class="invalid-feedback" style="display: block">' . $errors->first('cookie') . '</span>' : '' !!}
                        </div>
                        {!! Form::submit('Send', array('class' => 'btn btn-primary')) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </section>
@endsection