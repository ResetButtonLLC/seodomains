@extends('layouts.app')



@section('content')
<section class="container">
    <div class="row">
        <h1>Seo Domains</h1>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Url</th>
                    <th>Miralinks</th>
                    <th>Sape</th>
                    <th>Rotapost</th>
                    <th>Gogetlinks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $key => $value)
                <tr>
                    <td>{{$key}}</td>
                    <td>
                        @if($value['miralinks'])
                        <i>Название: </i> {{$value['miralinks']->name}}<br>
                        <i>Цена: </i> {{$value['miralinks']->placement_price}}<br>
                        <i>Цена написания: </i> {{$value['miralinks']->writing_price}}<br>
                        <i>Регион: </i> {{$value['miralinks']->region}}<br>
                        <i>Тема: </i> {{$value['miralinks']->theme}}<br>
                        <i>Индекс Google: </i> {{$value['miralinks']->google_index}}<br>
                        <i>Ссылок: </i> {{$value['miralinks']->links}}<br>
                        <i>Язык: </i> {{$value['miralinks']->language}}<br>
                        <i>Траффик: </i> {{$value['miralinks']->traffic}}
                        @endif
                    </td>
                    <td>
                        @if($value['sape'])
                        <i>Название: </i> {{$value['sape']->name}}<br>
                        <i>Цена: </i> {{$value['sape']->placement_price}}<br>
                        <i>Цена написания: </i> {{$value['sape']->writing_price}}<br>
                        <i>Регион: </i> {{$value['sape']->region}}<br>
                        <i>Тема: </i> {{$value['sape']->theme}}<br>
                        <i>Индекс Google: </i> {{$value['sape']->google_index}}<br>
                        <i>Ссылок: </i> {{$value['sape']->links}}<br>
                        <i>Язык: </i> {{$value['sape']->language}}<br>
                        <i>Траффик: </i> {{$value['sape']->traffic}}
                        @endif
                    </td>
                    <td>
                        @if($value['rotapost'])
                        <i>Название: </i> {{$value['rotapost']->name}}<br>
                        <i>Цена: </i> {{$value['rotapost']->placement_price}}<br>
                        <i>Цена написания: </i> {{$value['rotapost']->writing_price}}<br>
                        <i>Регион: </i> {{$value['rotapost']->region}}<br>
                        <i>Тема: </i> {{$value['rotapost']->theme}}<br>
                        <i>Индекс Google: </i> {{$value['rotapost']->google_index}}<br>
                        <i>Ссылок: </i> {{$value['rotapost']->links}}<br>
                        <i>Язык: </i> {{$value['rotapost']->language}}<br>
                        <i>Траффик: </i> {{$value['rotapost']->traffic}}
                        @endif
                    </td>
                    <td>
                        @if($value['gogetlinks'])
                        <i>Название: </i> {{$value['gogetlinks']->name}}<br>
                        <i>Цена: </i> {{$value['gogetlinks']->placement_price}}<br>
                        <i>Цена написания: </i> {{$value['gogetlinks']->writing_price}}<br>
                        <i>Регион: </i> {{$value['gogetlinks']->region}}<br>
                        <i>Тема: </i> {{$value['gogetlinks']->theme}}<br>
                        <i>Индекс Google: </i> {{$value['gogetlinks']->google_index}}<br>
                        <i>Ссылок: </i> {{$value['gogetlinks']->links}}<br>
                        <i>Язык: </i> {{$value['gogetlinks']->language}}<br>
                        <i>Траффик: </i> {{$value['gogetlinks']->traffic}}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection