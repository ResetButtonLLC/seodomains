@extends('layouts.app')



@section('content')
<section class="container">
    <div class="row">
        <h1>Seo Domains</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Url</th>
                    <th>Биржа</th>
                    <th>Цена размещения</th>
                    <th>Цена размещения с написанием</th>
                    <th>Тематика</th>
                    <th>Регион</th>
                    <th>Индекс страниц Google</th>
                    <th>Трафик</th>
                    <th>Дата добавления в биржу</th>
                    <th>Кол-во размещаемых ссылок</th>
                    <th>Язык</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $key => $value)
                <tr>
                    <td rowspan="4">{{ $loop->iteration }}</td>
                    <td rowspan="4">{{$key}}</td>
                    <td>Miralinks</td>
                    @if($value['miralinks'])
                    <td>{{$value['miralinks']->placement_price}}</td>
                    <td>{{$value['miralinks']->writing_price}}</td>
                    <td>{{$value['miralinks']->theme}}</td>
                    <td>{{$value['miralinks']->region}}</td>
                    <td>{{$value['miralinks']->google_index}}</td>
                    <td>{{$value['miralinks']->traffic}}</td>
                    <td>{{$value['miralinks']->created_at}}</td>
                    @else
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @endif
                    <td rowspan="4" class="text-center">{{$value['links']}}</td>
                    <td rowspan="4" class="text-center">{{$value['lang']}}</td>
                </tr>
                <tr>
                    <td>Sape</td>
                    @if($value['sape'])
                    <td>{{$value['sape']->placement_price}}</td>
                    <td>{{$value['sape']->writing_price}}</td>
                    <td>{{$value['sape']->theme}}</td>
                    <td>{{$value['sape']->region}}</td>
                    <td>{{$value['sape']->google_index}}</td>
                    <td>{{$value['sape']->traffic}}</td>
                    <td>{{$value['sape']->created_at}}</td>
                    @else
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @endif
                </tr>
                <tr>
                    <td>Rotapost</td>
                    @if($value['rotapost'])
                    <td>{{$value['rotapost']->placement_price}}</td>
                    <td>{{$value['rotapost']->writing_price}}</td>
                    <td>{{$value['rotapost']->theme}}</td>
                    <td>{{$value['rotapost']->region}}</td>
                    <td>{{$value['rotapost']->google_index}}</td>
                    <td>{{$value['rotapost']->traffic}}</td>
                    <td>{{$value['rotapost']->created_at}}</td>
                    @else
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @endif
                </tr>
                <tr>
                    <td>Gogetlinks</td>
                    @if($value['gogetlinks'])
                    <td>{{$value['gogetlinks']->placement_price}}</td>
                    <td>{{$value['gogetlinks']->writing_price}}</td>
                    <td>{{$value['gogetlinks']->theme}}</td>
                    <td>{{$value['gogetlinks']->region}}</td>
                    <td>{{$value['gogetlinks']->google_index}}</td>
                    <td>{{$value['gogetlinks']->traffic}}</td>
                    <td>{{$value['gogetlinks']->created_at}}</td>
                    @else
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection