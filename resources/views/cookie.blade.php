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
                <form method="POST" class="form mt-3" action="{{route('cookies.update')}}">
                    @csrf
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="name" value="collaborator">
                            <label class="form-check-label" for="exampleRadios2">
                                Collaborator
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="name" value="prposting">
                            <label class="form-check-label" for="exampleRadios2">
                                PrPosting
                            </label>
                        </div>

                    </div>

                    <div class="form-group">
                        <label for="cookie">Cookie из вкладки Network одной строкой, Copy Value по правой кнопке не использовать</label>
                        <input type="text" class="form-control" id="cookie" name="cookie">
                    </div>

                    <input class="btn btn-primary" type="submit" value="Send">
                </form>


            </div>
        </div>
    </section>
@endsection