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
    <div class="row">
        <h1>Seo Domains</h1>
        <div class="col-md-12">
            {!! Form::open(array('method' => 'GET', 'route' => ['domains'], 'class' => 'form form-row')) !!}
            <div class="col-md-4">
                <div class="row">
                    @isset ($update_dates)
                        @foreach ($update_dates as $stockname => $update_date)
                            <div class="control-group col-md-3">
                                {!! Form::label('resource['.$stockname.']', $stockname, [ 'class' => 'control-label' ]) !!}
                                <div class="controls">
                                    {!! Form::checkbox('resource['.$stockname.']', 'value', ['class' => 'form-control']) !!}
                                     <span class="badge badge-primary">{{$update_date}}</span>
                                </div>
                            </div>
                        @endforeach
                    @endisset
                </div>
            </div>

            <div class="col-md-1">{!! Form::text('price_from', null, ['class' => 'form-control', 'placeholder' => 'Цена от']); !!}</div>
            <div class="col-md-1">{!! Form::text('price_to', null, ['class' => 'form-control', 'placeholder' => 'Цена до']); !!}</div>
            <div class="col-md-2">{!! Form::text('theme', null, ['class' => 'form-control', 'placeholder' => 'Тематика']); !!}</div>
            <div class="col-md-2"> {!! Form::submit('Поиск', ['class'=>'btn btn-primary']) !!}</div>

            <div class="col-md-2"> {!! Form::submit('Экспорт', ['class'=>'btn btn-primary', 'name' => 'export']) !!}</div>
            {!! Form::close() !!}
        </div>
            <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>№</th>
                            <th>Url</th>
                            <th style="width: 200px">Цены</th>
                            <th style="width: 200px">Цена размещения с написанием</th>
                            <th>DR (ahrefs)</th>
                            <th>Вх/Исх домены (ahrefs)</th>
                            <th>Ahrefs positions top10</th>
                            <th>Ahrefs traffic top100</th>
                            <th>Тематика</th>
                            <th>Регион</th>
                            <th>Индекс страниц Google</th>
                            <th>Кол-во размещаемых ссылок</th>
                            <th>Язык</th>
                            <th>MajesticTF</th>
                            <th>MajesticCF</th>
                            <th>Описание</th>
                            <th>Дата добавления в биржу</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($domains as $key => $value)
                        <tr>
                            <td>{{ $value->id }}</td>
                            <td><a href="http://{{ $value->url }}">{{ $value->url }}</a></td>
                            <td id="stocklink">
                                @if($value->miralinks && $value->miralinks->placement_price)
                                <img src="https://www.miralinks.ru/favicon.ico"> 
                                @if(isset($value->miralinks->site_id))
                                <a href="https://www.miralinks.ru/catalog/profileView/{{ $value->miralinks->site_id }}" target="_blank">Miralinks - {{$value->miralinks->placement_price}}</a><br>
                                @else
                                Miralinks - {{$value->miralinks->placement_price}}<br>
                                @endif
                                @endif
                                @if($value->rotapost && $value->rotapost->placement_price)
                                <img src="https://www.rotapost.ru/i/favicon.ico"> Rotapost - {{$value->rotapost->placement_price}}<br>
                                @endif
                                @if($value->sape && $value->sape->placement_price)
                                <img src="https://static.sape.ru/pr-frontend/dist/pr/favicon.ico"> Sape - {{$value->sape->placement_price}}<br>
                                @endif
                                @if($value->gogetlinks && $value->gogetlinks->placement_price)
                                <img src="https://www.gogetlinks.net/favicon.ico"> Gogetlinks - {{$value->gogetlinks->placement_price}}<br>
                                @endif
                                @if($value->prnews && $value->prnews->price)
                                <img style="width:16px" src="https://www.prnews.ru/favicon.ico"> PR News - {{$value->prnews->price}}
                                @endif
                                
                            </td>
                            <td>
                                @if($value->miralinks && $value->miralinks->writing_price)
                                <img src="https://www.miralinks.ru/favicon.ico"> Miralinks - {{$value->miralinks->writing_price}}<br>
                                @endif
                                @if($value->rotapost && $value->rotapost->writing_price)
                                <img src="https://www.rotapost.ru/i/favicon.ico"> Rotapost - {{$value->rotapost->writing_price}}<br>
                                @endif
                                @if($value->sape && $value->sape->writing_price)
                                <img src="https://static.sape.ru/pr-frontend/dist/pr/favicon.ico"> Sape - {{$value->sape->writing_price}}<br>
                                @endif
                                @if($value->gogetlinks && $value->gogetlinks->writing_price)
                                <img src="https://www.gogetlinks.net/favicon.ico"> Gogetlinks - {{$value->gogetlinks->writing_price}}
                                @endif
                            </td>   
                            <td>{{$value->ahrefs_dr ? $value->ahrefs_dr : ''}}</td>
                            <td>{{$value->ahrefs_inlinks ? $value->ahrefs_inlinks : ''}}</td>
                            <td class="ahrefs_positions_top10">{{$value->ahrefs_positions_top10 ? $value->ahrefs_positions_top10 : ''}}</td>
                            <td class="ahrefs_traffic_top10">{{$value->ahrefs_traffic_top100 ? $value->ahrefs_traffic_top100 : ''}}</td>
                            <td>{{$value->miralinks ? $value->miralinks->theme : ''}}</td>
                            <td>{{$value->miralinks ? $value->miralinks->region : ''}}</td>
                            <td>{{$value->miralinks ? $value->miralinks->google_index : ''}}</td>
                            <td class="text-center">{{$value->miralinks ? $value->miralinks->links : ''}}</td>
                            <td class="text-center">{{$value->miralinks ? $value->miralinks->lang : ''}}</td>
                            <td class="majestic_cf">{{$value->majestic_cf ? $value->majestic_cf : ''}}</td>
                            <td class="majestic_tf">{{$value->majestic_tf ? $value->majestic_tf : ''}}</td>
                            <td>{{$value->miralinks ? $value->miralinks->desc : ''}}</td>
                            <td class="text-center">{{$value->created_at}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
        </div>
        <div class="container">
            {{ $domains->appends(request()->input())->links("pagination::bootstrap-4") }}
        </div>

</section>
@endsection