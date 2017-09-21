@extends('layouts.app')

@section('content')

    <section id="feature">
        <div class="container">
            <div class="col-md-12">
                <h1 class="classic-title"><span>Test List</span></h1>
            </div>
            <div class="row" >
                <!-- Start Service Icon 1 -->
                <div class="col-md-2">
                    <a href="#" onclick="Test('sql')">1. SQl Test </a>
                </div>
                <div class="col-md-10" id="test_list">
                    <div class="col-md-12 col-sm-6 col-xs-6 service-box fadeIn" data-animation="fadeIn" data-animation-delay="01">
                        <div>
                            This is test date area.
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="full-width">
        <div class="container" >
            <h1>&nbsp;</h1>
            <h1>&nbsp;</h1>
            <h1>&nbsp;</h1>
            <h1>&nbsp;</h1>
            <h1>&nbsp;</h1>
            <h1>&nbsp;</h1>
            <h1>&nbsp;</h1>
        </div>
    </section>

@endsection
