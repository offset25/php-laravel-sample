@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div>
                <h3 class="mb-3 mt-3 style-h3">Edit Shift</h3>

                <div class="post-section py-4">
                    <form method="POST" action="{{ route('job.update', ['id' =>$assignment->id]) }}">
                        @csrf
                        @if(session()->has('msg'))
                            <div class="alert alert-info">
                                {{ session('msg') }}.
                            </div>
                        @endif

                        <div class="form-group row">
                            <label for="title" class="col-md-2 col-form-label text-md-right">{{ __('Title') }}<span class="red"> *</span></label>

                            <div class="col-md-10">
                                <input  type="text" id="end_date" class="form-control{{ $errors->has('title') ? ' is-invalid' : '' }}" name="title" value="{{$assignment->title}}" required />
                                @if ($errors->has('title'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="title" class="col-md-2 col-form-label text-md-right">{{ __('Description') }}<span class="red"> *</span></label>

                            <div class="col-md-10">
                                <textarea id="description" rows="10" class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}" name="description" placeholder="Shift Description" required>{{$assignment->description}}</textarea>
                                @if ($errors->has('description'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('description') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="skills" class="col-md-2 col-form-label text-md-right">{{ __('Skills') }}</label>

                            <div class="col-md-4">
                                <select id="skills" name="skills[]"  class="select-style" multiple>
                                @foreach ($skills ?? [] as $skill)
                                    <!-- <div><input class="mr-2" type="checkbox" name="skills[]" value="{{ $skill->id}}"> <span>{{$skill->description}}</span></div> -->
                                    <option  value="{{ $skill->id}}"> {{$skill->description}}
                                @endforeach

                                @if ($errors->has('skills'))
                                    <span class="invalid-feedback" role="alert">

                                        <strong>{{ $errors->first('skills') }}</strong>
                                    </span>
                                @endif
                                </select>
                            </div>

                            <label for="weekdays" class="col-md-2 col-form-label text-md-right">{{ __('Week Days') }} <span class="red"> *</span> </label>

                            <div class="col-md-4">
                                <select id="weekdays" name="num_days[]" class="select-style" multiple>
                                    <option value="Sunday" id="days_of_the_week_sun">{{ __('Sunday') }}
                                    <option value="Monday"  id="days_of_the_week_mon">{{ __('Monday') }}
                                    <option value="Tuesday"  id="days_of_the_week_tue">{{ __('Tuesday') }}
                                    <option value="Wednesday"  id="days_of_the_week_wed">{{ __('Wednesday') }}
                                    <option value="Thursday"  id="days_of_the_week_thu">{{ __('Thursday') }}
                                    <option value="Friday"  id="days_of_the_week_fri">{{ __('Friday') }}
                                    <option value="Saturday"  id="days_of_the_week_sat">{{ __('Saturday') }}
                                </select>
                             </div>
                        </div>
                        <hr />

                        <div class="form-group row">
                            <label for="start_date" class="col-md-2 col-form-label text-md-right">{{ __('Start Date') }} <span class="red"> *</span></label>

                            <div class="col-md-4">
                                <div class="input-group date" id='datetimepicker1'>
                                <input  type="text" id="start_date" class="form-control{{ $errors->has('date_start') ? ' is-invalid' : '' }}" name="date_start" value="{{$assignment->date_start}}" required>
                                    <div class="input-group-addon d-flex justify-content-center align-items-center" >
                                        <i class="fa fa-th"></i>
                                    </div>
                                </div>
                            </div>

                            <label for="end_date" class="col-md-2 col-form-label text-md-right">{{ __('End Date') }} <span class="red"> *</span></label>

                            <div class="col-md-4">
                                <div class="input-group date" id='datetimepicker2'>
                                    <input  type="text" id="end_date" class="form-control{{ $errors->has('date_end') ? ' is-invalid' : '' }}" name="date_end" value="{{$assignment->date_end}}" required>
                                    <div class="input-group-addon d-flex justify-content-center align-items-center" >
                                        <i class="fa fa-th"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="start_time" class="col-md-2 col-form-label text-md-right">{{ __('Start Time') }} <span class="red"> *</span></label>

                            <div class="col-md-4">
                                <div class='input-group date' id='datetimepicker3'>
                                    <input type='text' id="start_time" class="form-control{{ $errors->has('time_start') ? ' is-invalid' : '' }}" name="time_start" value="{{$assignment->time_start}}" required/>
                                    <div class="input-group-addon d-flex justify-content-center align-items-center" >
                                    <i class="fa fa-clock-o fa-lg"></i>
                                    </div>
                                </div>
                            </div>

                            <label for="end_time" class="col-md-2 col-form-label text-md-right">{{ __('End Time') }}<span class="red"> *</span> </label>

                            <div class="col-md-4">
                                <div class='input-group date' id='datetimepicker4'>
                                    <input type='text' id="end_time" class="form-control{{ $errors->has('time_end') ? ' is-invalid' : '' }}" name="time_end" value="{{$assignment->time_end}}" required/>
                                    <div class="input-group-addon d-flex justify-content-center align-items-center" >
                                        <i class="fa fa-clock-o fa-lg"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="gender" class="col-md-2 col-form-label text-md-right">{{ __('Gender') }}</label>

                            <div class="col-md-4">
                                <select name="client_gender" id="gender" class="form-control{{ $errors->has('gender') ? ' is-invalid' : '' }}">
                                    <option value="No Preference">No Preference</option>
                                    <option value="Male" @if($assignment->client_gender == "Male") selected @endif>Male</option>
                                    <option value="Female" @if($assignment->client_gender == "Female") selected @endif>Female</option>
                                </select>
                                @if ($errors->has('gender'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('gender') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <label for="language" class="col-md-2 col-form-label text-md-right">{{ __('Language') }} <span class="red"> *</span> </label>

                            <div class="col-md-4 d-flex justify-content-center align-items-center">
                                <select id="languages" name="language[]" class="select-style" multiple>
                                @foreach ($languages as $language)
                                    @if ($loop->first)
                                        <option value="{{ $language['id']}}"> {{$language['name']}}
                                    @else
                                        <option value="{{ $language['id']}}"> {{$language['name']}}
                                    @endif
                                @endforeach
                                @if ($errors->has('language'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('language') }}</strong>
                                    </span>
                                @endif
</select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="live_in_shifts" class="col-md-2 col-form-label text-md-right">{{ __('Live in shifts') }}</label>

                            <div class="col-md-4 d-flex align-items-center">
                                <input type="checkbox" id="live_in_shifts" name="live_in_shifts" @if($assignment->live_in) checked @endif>
                                @if ($errors->has('live_in_shifts'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('live_in_shifts') }}</strong>
                                    </span>
                                @endif
                            </div>


                            <label for="drives" class="col-md-2 col-form-label text-md-right">{{ __('Drives') }}</label>

                            <div class="col-md-4 d-flex align-items-center">

                                <input type="checkbox" id="drives" name="drives"  @if($assignment->drive) checked @endif>
                                @if ($errors->has('drives'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('drives') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <label for="night_shift" class="col-md-2 col-form-label text-md-right">{{ __('Night shift') }}</label>

                            <div class="col-md-4 d-flex align-items-center">
                                <input type="checkbox" id="night_shift" name="night_shift" @if($assignment->night_shift) checked @endif>
                                @if ($errors->has('night_shift'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('night_shift') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="address1" class="col-md-2 col-form-label text-md-right">{{ __('Address') }}</label>

                            <div class="col-md-4">
                                <input type="text" id="address1" class="form-control{{ $errors->has('shift_address1') ? ' is-invalid' : '' }}" name="shift_address1" placeholder="Address" value="{{$assignment->shift_address1}}">
                                @if ($errors->has('address1'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('address1') }}</strong>
                                    </span>
                                @endif
 </div>

                            <label for="address2" class="col-md-2 col-form-label text-md-right">{{ __('Address2') }}</label>

                            <div class="col-md-4">
                                <input type="text" id="address2" class="form-control{{ $errors->has('shift_address2') ? ' is-invalid' : '' }}" name="shift_address2" placeholder="Address2" value="{{$assignment->shift_address2}}" >
                                @if ($errors->has('address2'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('address2') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="city" class="col-md-2 col-form-label text-md-right">{{ __('City') }} <span class="red"> *</span></label>

                            <div class="col-md-4">
                                <input type="text" id="city" class="form-control{{ $errors->has('shift_city') ? ' is-invalid' : '' }}" name="shift_city" placeholder="City" value="{{$assignment->shift_city}}" required>
                                @if ($errors->has('city'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('city') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <label for="state" class="col-md-2 col-form-label text-md-right">{{ __('State') }}<span class="red"> *</span></label>

                            <div class="col-md-4">
                                <select id="state" name="shift_state"  class="form-control{{ $errors->has('state') ? ' is-invalid' : '' }}">
                                @foreach ($states as $state)
                                    <option value="{{$state}}" @if($assignment->shift_state==$state ) selected='selected' @endif >{{$state}}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="zip" class="col-md-2 col-form-label text-md-right">{{ __('Zip') }}<span class="red"> *</span></label>

                            <div class="col-md-4">
                                <input type="text" id="zip" class="form-control{{ $errors->has('shift_zip') ? ' is-invalid' : '' }}" name="shift_zip" placeholder="Zip" value="{{$assignment->shift_zip}}" required>
                                @if ($errors->has('zip'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('zip') }}</strong>
                                    </span>
                                @endif
                            </div>
<label for="rate_details" class="col-md-2 col-form-label text-md-right">{{ __('Rate') }}</label>

                            <div class="col-md-4">
                                <input type="text" id="rate_details" class="form-control{{ $errors->has('rate_details') ? ' is-invalid' : '' }}" name="rate_details" placeholder="" value="{{$assignment->rate_details}}" required>
                                @if ($errors->has('rate_details'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('rate_details') }}</strong>
                                    </span>
                                @endif
                            </div>

                        </div>

                         <div class="form-group row">
                            <label for="network_only" class="col-md-2 col-form-label text-md-right">{{ __('In Network only') }}</label>

                            <div class="col-md-4 d-flex align-items-center">
                                <input type="checkbox" id="network_only" name="network_only" @if($assignment->private) checked @endif>
                                @if ($errors->has('network_only'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('network_only') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-4 offset-md-2">
                                <button type="submit" class="btn btn-primary post-btn">
                                    {{ __('Save') }}
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
