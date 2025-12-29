@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1>Статистика</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <table class="table">
                    <tbody>
                    <tr>
                        <th scope="row">1</th>
                        <td>Всего домов</td>
                        <td>999</td>
                    </tr>
                    <tr>
                        <th scope="row">2</th>
                        <td>Всего участков</td>
                        <td>9990</td>
                    </tr>
                    <tr>
                        <th scope="row">3</th>
                        <td>Без координат</td>
                        <td>3</td>
                    </tr>
                    <tr>
                        <th scope="row">3</th>
                        <td>Без формы</td>
                        <td>10</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
