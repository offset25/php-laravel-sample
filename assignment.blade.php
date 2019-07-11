@extends('layouts.app')

@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div>
                <h3 class="mb-3 mt-3">{{ $assignment->title }}</h3>
            </div>
            <div>
                <div class="ui top attached tabular menu">
                    <div class="item active" data-tab="matches">Matches</div>
                    <div class="item" data-tab="applications">Applications</div>
                    <div class="item" data-tab="hired">Hired</div>
                    <div class="item" data-tab="caregivers">All Caregivers</div>
                </div>
                <div class="ui bottom attached tab segment active" data-tab="matches">
                    <caregiver-search :id="{{ $assignment->id }}"></caregiver-search>
                </div>
                <div class="ui bottom attached tab segment" data-tab="applications">
                    <assignment-applications :id="{{ $assignment->id }}"></assignment-applications>
                </div>
                <div class="ui bottom attached tab segment" data-tab="hired">
                    <assignment-hired :id="{{ $assignment->id }}"></assignment-hired>
                </div>
                <div class="ui bottom attached tab segment" data-tab="caregivers">
                    <assignment-caregivers :id="{{ $assignment->id }}"></assignment-caregivers>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $('.menu .item').tab({
        history: true,
        historyType: 'hash',
        path: '/details',
        alwaysRefresh: true,
    });
</script>
@endsection
