<?php
    
    namespace App\Http\Controllers;
    
    use App\Department;
    use App\Helpers\ReportsHelper;
    use App\Helpers\ShiftsHelper;
    use App\Location;
    use App\Shift;
    use Illuminate\Support\Facades\DB;
    use App\User;
    use Illuminate\Http\Request;
    use ImmutableCarbon\Carbon;
    use Illuminate\Support\Str;
    use Symfony\Component\HttpFoundation\StreamedResponse;
    
    class ReportsController extends Controller
    {
        public function __construct()
        {
            $this->middleware('auth');
        }
        
        public function index ()
        {
            return view($this->spaView);
        }
        
        private function __fetchHeadCountReport($request)
        {
            $filters = [];
            if($request['start_time']) {
                $filters = [
                    'start_time' => $request['start_time'],
                    'end_time' => $request['end_time'],
                ];
            }
            $method = '__fetchHeadCountData'.User::roleName();
            $reportData = $this->$method($filters);
            $data = [];
            if ($reportData) {
                foreach($reportData as $employee) {
                    $data[] = [
                        $employee->first_name,
                        $employee->last_name,
                        $employee->department_name,
                        $employee->shift,
                        $employee->supervisor_name,
                        $employee->location_name,
                        $employee->city,
                        $employee->state,
                        $employee->position_status,
                        $employee->jobTag,
                        $employee->pay_rate,
                        $employee->hire_date,
                        $employee->birth_date,
                        $employee->email,
                        $employee->phone,
                        $employee->address,
                    ];
                }
            }
            
            return [
                'fields' => [
                    [
                        trans('First Name'),
                        trans('Last Name'),
                        trans('Department Name'),
                        trans('Shift'),
                        trans('Supervisor'),
                        trans('Location'),
                        trans('City'),
                        trans('State'),
                        trans('Position Status'),
                        trans('Job Tag'),
                        trans('Pay Rate'),
                        trans('Hire Date'),
                        trans('Birth Date'),
                        trans('Email'),
                        trans('Phone'),
                        trans('Address'),
                    ]
                ],
                'data' => $data
            ];
        }
        
        private function __fetchHeadCountDataManager ($filters = [])
        {
            $user = new User();
            $employees = $user->getManagerEmployeesList($filters);
            //dump($employees);
            $employees->transform(
                function ($employee) {
                    $employee->department_name = $employee->department->name;
                    $employee->shift = 'N/A';
                    $employee->name = $employee->first_name . ' ' . $employee->last_name;
                    $employee->supervisor_name = $employee->supervisor->first_name . ' ' . $employee->supervisor->last_name;
                    $employee->pay_rate = $employee->hourly_rate;
                    $employee->location_name = $employee->location->title;
                    $employee->jobTag = $employee->jobTag->title;
                    $employee->hire_date = \Carbon\Carbon::parse($employee->hiring_date)->format('m-d-Y');
                    $employee->birth_date = !empty($employee->birth_date) ? $employee->birth_date : 'N/A';
                    
                    //$employee->state = 'N/A';
                    //$employee->employee_type = $employee->employee_type;
                    //$employee->employee_class = $employee->employee_class;
                    //$employee->position_status = $employee->position_status;
                    
                    $employee->address = $employee->location->title . ', ' . $employee->city;
                    return $employee;
                });
            
            return $employees;
        }
    
    
        private function __fetchHeadCountDataEmployee ($filters = [])
        {
            $user = new User();
            $employees = $user->getEmployeeColleaguesList($filters);
            //dump($employees);
            $employees->transform(
                function ($employee) {
                    $employee->department_name = $employee->department->name;
                    $employee->shift = 'N/A';
                    $employee->name = $employee->first_name . ' ' . $employee->last_name;
                    $employee->supervisor_name = $employee->supervisor->first_name . ' ' . $employee->supervisor->last_name;
                    $employee->pay_rate = $employee->hourly_rate;
                    $employee->location_name = $employee->location->title;
                    $employee->jobTag = $employee->jobTag->title;
                    $employee->hire_date = \Carbon\Carbon::parse($employee->hiring_date)->format('m-d-Y');
                    $employee->birth_date = !empty($employee->birth_date) ? $employee->birth_date : 'N/A';
                
                    //$employee->state = 'N/A';
                    //$employee->employee_type = $employee->employee_type;
                    //$employee->employee_class = $employee->employee_class;
                    //$employee->position_status = $employee->position_status;
                
                    $employee->address = $employee->location->title . ', ' . $employee->city;
                    return $employee;
                });
        
            
            return $employees;
        }
    
        private function __fetchHeadCountDataAdmin ($filters = [])
        {
            $user = new User();
            $employees = $user->getEmployeesList($filters);
            //dump($employees);
            $employees->transform(
                function ($employee) {
                    $employee->department_name = $employee->department->name;
                    $employee->shift = 'N/A';
                    $employee->name = $employee->first_name . ' ' . $employee->last_name;
                    $employee->supervisor_name = $employee->supervisor->first_name . ' ' . $employee->supervisor->last_name;
                    $employee->pay_rate = $employee->hourly_rate;
                    $employee->location_name = $employee->location->title;
                    $employee->jobTag = $employee->jobTag->title;
                    $employee->hire_date = \Carbon\Carbon::parse($employee->hiring_date)->format('m-d-Y');
                    $employee->birth_date = !empty($employee->birth_date) ? $employee->birth_date : 'N/A';
                
                    //$employee->state = 'N/A';
                    //$employee->employee_type = $employee->employee_type;
                    //$employee->employee_class = $employee->employee_class;
                    //$employee->position_status = $employee->position_status;
                
                    $employee->address = $employee->location->title . ', ' . $employee->city;
                    return $employee;
                });
        
            return $employees;
        }
        
        public function headcount ()
        {
            $filters = [];
            if(request('start_time')) {
               $filters = [
                   'start_time' => request('start_time'),
                   'end_time' => request('end_time'),
               ];
            }
            $method = '__fetchHeadCountData'.User::roleName();
            $employees = $this->$method($filters);
            return response()->json($this->fetchAppData($employees));
        }
        
        public function exportReport (Request $request, $report = null)
        {
            $report = ucwords(Str::camel($report));
            //dump($report);
            $method = '__fetch' . $report . 'Report';
            $reportData = $this->$method($request);
            $file_data = array_merge($reportData['fields'], $reportData['data']);
            
            return new StreamedResponse(
                function () use ($file_data) {
                    // A resource pointer to the output stream for writing the CSV to
                    $handle = fopen('php://output', 'w');
                    foreach ($file_data as $row) {
                        // Loop through the data and write each entry as a new row in the csv
                        fputcsv($handle, $row);
                    }
                    fclose($handle);
                },
                200,
                [
                    'Content-type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename=members.csv'
                ]
            );
        }
        
        private function __fetchHoursWadgesSummaryReport ()
        {
            $method = '__fetchHoursWadgesSummary' . User::roleName();
            $reportData = $this->$method();
            $data = [];
            if ($reportData) {
                foreach ($reportData as $report)
                    $data[] = [
                        $report->location,
                        $report->dept_name,
                        $report->shift,
                        $report->avg_pay_rate,
                        $report->budget_hours_total,
                        $report->budget_cost_total
                    ];
            }
            return [
                'fields' => [
                    [
                        trans('Location'),
                        trans('Department Name'),
                        trans('Shift'),
                        trans('Avg Pay Rate'),
                        trans('Budget Hours Total'),
                        trans('Budget Cost Total')
                    ]
                ],
                'data' => $data
            ];
        }
    
    
        private function __fetchHoursWadgesSummaryEmployee()
        {
            $departmentModel = new Department();
            $departments = $departmentModel
                ->has('user')
                /*->whereHas('shifts', function($query){
                    $query->whereBetween(DB::raw('start_time'), [request('start_time'), request('end_time')]);
                    //$query->where('start_time', '>=', request('start_time'). '00:00:00');
                    //$query->where('end_time', '<=', request('end_time'). '00:00:00');
                })*/
                ->with(['user', 'shifts', 'locations'])
                //   ->toSql();
                ->get();
            //dd($departments);
            $departments->transform(
                function ($department) {
                    $totalShiftHours = 0;
                    $totalShiftMinutes = 0;
                    $totalMinutesBudget = 0;
                    $empBudgetCost = [];
                
                
                    $department->dept_name = $department->name;
                    $department->avg_pay_rate = number_format((float)$department->user->avg('hourly_rate'), 2, '.', '');
                    //$department->avg_pay_rate = $department->user->avg('hourly_rate');
                
                    if ($department->shifts->isEmpty()) {
                        $department->shiftHours_count = '0';
                        $department->shift = 0;
                    } else {
                        $shiftHours = [];
                        $shiftMinutes = [];
                        $userShiftsHours = [];
                        $userShiftMinutes = [];
                        $deptShifts = $department->shifts->filter(
                            function ($shift) {
                                return ($shift->start_time >= request('start_time')) && ($shift->end_time <= request('end_time'));
                            });
                    
                        $department->location = '';
                        $department->shift = $deptShifts->count();
                        foreach ($deptShifts as $shift) {
                            $startTime = new \DateTime($shift->start_time);
                            $endTime = new \DateTime($shift->end_time);
                        
                            $interval = $endTime->diff($startTime);
                            $shiftHours[] = $interval->h + ($interval->days * 24);
                            $shiftMinutes[] = $interval->i + ($interval->days * 24);
                            $shiftMinutes = array_filter($shiftMinutes);
                            $userShiftsHours[$shift->user_id][] = $interval->h + ($interval->days * 24);
                            $userShiftMinutes[$shift->user_id][] = $interval->i + ($interval->days * 24);
                        }
                        //dump($userShiftsHours);
                        $totalShiftHours = 0;
                        $totalShiftMinutes = 0;
                        $employeeShiftHours = 0;
                        $employeeShiftMinutes = 0;
                    
                        foreach ($userShiftsHours as $user => $hours) {
                            $employeeShiftHours = array_sum($userShiftsHours[$user]);
                            $employeeShiftMinutes += array_sum($userShiftMinutes[$user]);
                        
                            if ($employeeShiftMinutes > 30) {
                                $convertedData = ShiftsHelper::convertFromMinutes($employeeShiftMinutes);
                                $employeeShiftHours += (int)$convertedData['hours'];
                                $employeeShiftMinutes += $convertedData['minutes'];
                            }
                        
                            if ($employeeShiftHours > 40) {
                                $totalMinutesBudget = ($employeeShiftMinutes / 60) * auth()->user()->company->overtime_pay;
                            
                                //calculating regular shifting hours budget
                                $empBudgetCost[] = (int)40 * $department->avg_pay_rate;
                            
                                //calculating overtime hours budget.
                                $extraHours = $employeeShiftHours - 40;
                                $empBudgetCost[] = ($extraHours * auth()->user()->company->overtime_pay) + $totalMinutesBudget;
                            } else {
                                $totalMinutesBudget = ($employeeShiftMinutes / 60) * $department->avg_pay_rate;
                                $empBudgetCost[] = ($employeeShiftHours * $department->avg_pay_rate) + $totalMinutesBudget;
                            }
                        
                            $totalShiftHours += $employeeShiftHours;
                            $totalShiftMinutes += $employeeShiftMinutes;
                        }
                    }
                    //dump($empBudgetCost);
                
                    $department->budget_hours_total = $totalShiftHours . ' Hours | ' . $totalShiftMinutes . ' Minutes';
                    $department->budget_cost_total = array_sum($empBudgetCost);
                    $department->budget_cost_total = '$' . $department->budget_cost_total;
                    $department->avg_pay_rate = '$' . $department->avg_pay_rate;
                    return $department;
                });
        
            return $departments;
        }
        
        private function __fetchHoursWadgesSummaryManager()
        {
            $departmentModel = new Department();
            $departments = $departmentModel
                ->has('user')
                /*->whereHas('shifts', function($query){
                    $query->whereBetween(DB::raw('start_time'), [request('start_time'), request('end_time')]);
                    //$query->where('start_time', '>=', request('start_time'). '00:00:00');
                    //$query->where('end_time', '<=', request('end_time'). '00:00:00');
                })*/
                ->with(['user', 'shifts', 'locations'])
                //   ->toSql();
                ->get();
            //dd($departments);
            $departments->transform(
                function ($department) {
                    $totalShiftHours = 0;
                    $totalShiftMinutes = 0;
                    $totalMinutesBudget = 0;
                    $empBudgetCost = [];
                
                
                    $department->dept_name = $department->name;
                    $department->avg_pay_rate = number_format((float)$department->user->avg('hourly_rate'), 2, '.', '');
                    //$department->avg_pay_rate = $department->user->avg('hourly_rate');
                
                    if ($department->shifts->isEmpty()) {
                        $department->shiftHours_count = '0';
                        $department->shift = 0;
                    } else {
                        $shiftHours = [];
                        $shiftMinutes = [];
                        $userShiftsHours = [];
                        $userShiftMinutes = [];
                        $deptShifts = $department->shifts->filter(
                            function ($shift) {
                                return ($shift->start_time >= request('start_time')) && ($shift->end_time <= request('end_time'));
                            });
                    
                        $department->location = '';
                        $department->shift = $deptShifts->count();
                        foreach ($deptShifts as $shift) {
                            $startTime = new \DateTime($shift->start_time);
                            $endTime = new \DateTime($shift->end_time);
                        
                            $interval = $endTime->diff($startTime);
                            $shiftHours[] = $interval->h + ($interval->days * 24);
                            $shiftMinutes[] = $interval->i + ($interval->days * 24);
                            $shiftMinutes = array_filter($shiftMinutes);
                            $userShiftsHours[$shift->user_id][] = $interval->h + ($interval->days * 24);
                            $userShiftMinutes[$shift->user_id][] = $interval->i + ($interval->days * 24);
                        }
                        //dump($userShiftsHours);
                        $totalShiftHours = 0;
                        $totalShiftMinutes = 0;
                        $employeeShiftHours = 0;
                        $employeeShiftMinutes = 0;
                    
                        foreach ($userShiftsHours as $user => $hours) {
                            $employeeShiftHours = array_sum($userShiftsHours[$user]);
                            $employeeShiftMinutes += array_sum($userShiftMinutes[$user]);
                        
                            if ($employeeShiftMinutes > 30) {
                                $convertedData = ShiftsHelper::convertFromMinutes($employeeShiftMinutes);
                                $employeeShiftHours += (int)$convertedData['hours'];
                                $employeeShiftMinutes += $convertedData['minutes'];
                            }
                        
                            if ($employeeShiftHours > 40) {
                                $totalMinutesBudget = ($employeeShiftMinutes / 60) * auth()->user()->company->overtime_pay;
                            
                                //calculating regular shifting hours budget
                                $empBudgetCost[] = (int)40 * $department->avg_pay_rate;
                            
                                //calculating overtime hours budget.
                                $extraHours = $employeeShiftHours - 40;
                                $empBudgetCost[] = ($extraHours * auth()->user()->company->overtime_pay) + $totalMinutesBudget;
                            } else {
                                $totalMinutesBudget = ($employeeShiftMinutes / 60) * $department->avg_pay_rate;
                                $empBudgetCost[] = ($employeeShiftHours * $department->avg_pay_rate) + $totalMinutesBudget;
                            }
                        
                            $totalShiftHours += $employeeShiftHours;
                            $totalShiftMinutes += $employeeShiftMinutes;
                        }
                    }
                    //dump($empBudgetCost);
                
                    $department->budget_hours_total = $totalShiftHours . ' Hours | ' . $totalShiftMinutes . ' Minutes';
                    $department->budget_cost_total = array_sum($empBudgetCost);
                    $department->budget_cost_total = '$' . $department->budget_cost_total;
                    $department->avg_pay_rate = '$' . $department->avg_pay_rate;
                    return $department;
                });
        
            return $departments;
        }
        
        private function __fetchHoursWadgesSummaryAdmin()
        {
            //dump('fetching report params: ', request('start_time'), request('end_time'));
            $departmentModel = new Department();
            $departments = $departmentModel
                ->has('user')
                /*->whereHas('shifts', function($query){
                    $query->whereBetween(DB::raw('start_time'), [request('start_time'), request('end_time')]);
                    //$query->where('start_time', '>=', request('start_time'). '00:00:00');
                    //$query->where('end_time', '<=', request('end_time'). '00:00:00');
                })*/
                ->with(['user', 'shifts', 'locations'])
                ->where(['company_id' => auth()->user()->company_id])
                //   ->toSql();
                ->get();
            //dd($departments);
            $departments->transform(
                function ($department) {
                    $totalShiftHours = 0;
                    $totalShiftMinutes = 0;
                    $totalMinutesBudget = 0;
                    $empBudgetCost = [];
                    
                    $department->dept_name = $department->name;
                    $department->avg_pay_rate = number_format((float)$department->user->avg('hourly_rate'), 2, '.', '');
                    
                    if ($department->shifts->isEmpty()) {
                        $department->shiftHours_count = '0';
                        $department->shift = 0;
                    } else {
                        $shiftHours = [];
                        $shiftMinutes = [];
                        $userShiftsHours = [];
                        $userShiftMinutes = [];
                        $deptShifts = $department->shifts->filter(
                            function ($shift) {
                                return ($shift->start_time >= request('start_time')) && ($shift->end_time <= request('end_time'));
                            });
    
                        $department->location = $deptShifts->location->pluck('title');
                        $department->shift = $deptShifts->count();
                        foreach ($deptShifts as $shift) {
                            $startTime = new \DateTime($shift->start_time);
                            $endTime = new \DateTime($shift->end_time);
                            
                            $interval = $endTime->diff($startTime);
                            $shiftHours[] = $interval->h + ($interval->days * 24);
                            $shiftMinutes[] = $interval->i + ($interval->days * 24);
                            $shiftMinutes = array_filter($shiftMinutes);
                            $userShiftsHours[$shift->user_id][] = $interval->h + ($interval->days * 24);
                            $userShiftMinutes[$shift->user_id][] = $interval->i + ($interval->days * 24);
                        }
                        //dump($userShiftsHours);
                        $totalShiftHours = 0;
                        $totalShiftMinutes = 0;
                        $employeeShiftHours = 0;
                        $employeeShiftMinutes = 0;
                        
                        foreach ($userShiftsHours as $user => $hours) {
                            $employeeShiftHours = array_sum($userShiftsHours[$user]);
                            $employeeShiftMinutes += array_sum($userShiftMinutes[$user]);
                            
                            if ($employeeShiftMinutes > 30) {
                                $convertedData = ShiftsHelper::convertFromMinutes($employeeShiftMinutes);
                                $employeeShiftHours += (int)$convertedData['hours'];
                                $employeeShiftMinutes += $convertedData['minutes'];
                            }
                            
                            if ($employeeShiftHours > 40) {
                                $totalMinutesBudget = ($employeeShiftMinutes / 60) * auth()->user()->company->overtime_pay;
                                
                                //calculating regular shifting hours budget
                                $empBudgetCost[] = (int)40 * $department->avg_pay_rate;
                                
                                //calculating overtime hours budget.
                                $extraHours = $employeeShiftHours - 40;
                                $empBudgetCost[] = ($extraHours * auth()->user()->company->overtime_pay) + $totalMinutesBudget;
                            } else {
                                $totalMinutesBudget = ($employeeShiftMinutes / 60) * $department->avg_pay_rate;
                                $empBudgetCost[] = ($employeeShiftHours * $department->avg_pay_rate) + $totalMinutesBudget;
                            }
                            
                            $totalShiftHours += $employeeShiftHours;
                            $totalShiftMinutes += $employeeShiftMinutes;
                        }
                    }
                    //dump($empBudgetCost);
                    
                    //$department->employee_id = $department->user->pluck('id');
                    $department->budget_hours_total = $totalShiftHours . ' Hours | ' . $totalShiftMinutes . ' Minutes';
                    $department->budget_cost_total = array_sum($empBudgetCost);
                    $department->budget_cost_total = '$' . $department->budget_cost_total;
                    $department->avg_pay_rate = '$' . $department->avg_pay_rate;
                    return $department;
                });
            
            return $departments;
        }
        
        public function hoursWadgesSummary ()
        {
            $method = '__fetchHoursWadgesSummary'.User::roleName();
            $shifts = $this->$method();
            return response()->json($this->fetchAppData($shifts));
        }
    
        public function __fetchAttendanceRecordReport()
        {
            $method = '__fetchAttendanceRecord' . User::roleName();
            $attendance = $this->$method();
            $data = [];
            if ($attendance) {
                foreach ($attendance as $report)
                    $data[] = [
                        //$report->id,
                        $report->first_name,
                        $report->last_name,
                        $report->location_name,
                        $report->department_name,
                        $report->scheduled_days,
                        $report->shifts_worked,
                        $report->sick,
                        $report->no_show,
                        $report->attendance
                    ];
            }
            return [
                'fields' => [
                    [
                        //trans('ID'),
                        trans('First Name'),
                        trans('Last Name'),
                        trans('Location Name'),
                        trans('Department Name'),
                        trans('Scheduled Days'),
                        trans('Shifts Worked'),
                        trans('Sick'),
                        trans('No Show'),
                        trans('Attendance')
                    ]
                ],
                'data' => $data
            ];
        }
    
    
        private function __fetchAttendanceRecordEmployee()
        {
            $users = User::where(['company_id' => auth()->user()->company_id])
                ->role('employee')
                ->where(function ($query){
                    $query->where('location_id', User::locId());
                    $query->orWhere('department_id', User::deptId());
                })
                ->where('id', '!=', auth()->user()->id)
                ->get();
            //dump($users->count());
            $users = $users->transform(function($user){
                $user->location_name = $user->location->title;
                $user->department_name = $user->department->name;
            
                //filtering weekly shifts
                $userShifts = $user->shift->filter(
                    function ($shift) {
                        return ($shift->start_time >= request('start_time')) && ($shift->end_time <= request('end_time'));
                    });
                //dump($userShifts);
                $user->hours = 0;
                $user->pay = 0;
                if($userShifts) {
                    $hoursDiff = [];
                    foreach ($userShifts as $shift) {
                        $start = \Carbon\Carbon::parse($shift->start_time);
                        $end = \Carbon\Carbon::parse($shift->end_time);
                    
                        $hoursDiff[] = $end->diffInHours($start);
                    }
                    //dump($hoursDiff);
                    $user->hours = array_sum($hoursDiff);
                    $user->pay = '$'.$user->hours * $user->hourly_rate;
                }
            
                $user->scheduled_days = $userShifts->count();
                $user->sick = $userShifts->filter(function($value, $key){
                    return $value->attendance == 3;
                })->count();
            
                $user->no_show = $userShifts->filter(function($value, $key){
                    return $value->attendance == 2;
                })->count();
            
                $user->shifts_worked = $user->scheduled_days - ($user->sick + $user->no_show);
                $attendedShifts = $user->scheduled_days - ($user->no_show + $user->sick);
                try{
                    $user->attendance = '%'. round(($attendedShifts/$user->scheduled_days) * 100, 2);
                } catch (\Exception $e) {
                
                }
                return $user;
            });
            // Either one of you can reach out to me on Skype as well haasdamon7 is my Skype username
            //dd($users->count());
            return $users;
        }
        
        private function __fetchAttendanceRecordManager()
        {
            $users = User::where(['company_id' => auth()->user()->company_id, 'user_type' => 2])
                ->where(function ($query){
                    $query->where('location_id', User::locId());
                    $query->orWhere('department_id', User::deptId());
                })
                ->get();
            //dump($users->count());
            $users = $users->transform(function($user){
                $user->location_name = $user->location->title;
                $user->department_name = $user->department->name;
                
                //filtering weekly shifts
                $userShifts = $user->shift->filter(
                    function ($shift) {
                        return ($shift->start_time >= request('start_time')) && ($shift->end_time <= request('end_time'));
                    });
                //dump($userShifts);
                $user->hours = 0;
                $user->pay = 0;
                if($userShifts) {
                    $hoursDiff = [];
                    foreach ($userShifts as $shift) {
                        $start = \Carbon\Carbon::parse($shift->start_time);
                        $end = \Carbon\Carbon::parse($shift->end_time);
                        
                        $hoursDiff[] = $end->diffInHours($start);
                    }
                    //dump($hoursDiff);
                    $user->hours = array_sum($hoursDiff);
                    $user->pay = '$'.$user->hours * $user->hourly_rate;
                }
                
                $user->scheduled_days = $userShifts->count();
                $user->sick = $userShifts->filter(function($value, $key){
                    return $value->attendance == 3;
                })->count();
    
                $user->no_show = $userShifts->filter(function($value, $key){
                    return $value->attendance == 2;
                })->count();
                
                $user->shifts_worked = $user->scheduled_days - ($user->sick + $user->no_show);
                $attendedShifts = $user->scheduled_days - ($user->no_show + $user->sick);
                try{
                    $user->attendance = '%'. round(($attendedShifts/$user->scheduled_days) * 100, 2);
                } catch (\Exception $e) {
                
                }
                return $user;
            });
            // Either one of you can reach out to me on Skype as well haasdamon7 is my Skype username
            //dd($users->count());
            return $users;
        }
    
        private function __fetchAttendanceRecordAdmin()
        {
            $users = User::where(['company_id' => auth()->user()->company_id, 'user_type' => 2])->get();
            //dump($users->count());
            $users = $users->transform(function($user){
                $user->location_name = $user->location->title;
                $user->department_name = $user->department->name;
            
                //filtering weekly shifts
                $userShifts = $user->shift->filter(
                    function ($shift) {
                        return ($shift->start_time >= request('start_time')) && ($shift->end_time <= request('end_time'));
                    });
    
                $user->hours = 0;
                $user->pay = 0;
                if($userShifts) {
                    $hoursDiff = [];
                    foreach ($userShifts as $shift) {
                        $start = \Carbon\Carbon::parse($shift->start_time);
                        $end = \Carbon\Carbon::parse($shift->end_time);
            
                        $hoursDiff[] = $end->diffInHours($start);
                    }
                    //dump($hoursDiff);
                    $user->hours = array_sum($hoursDiff);
                    $user->pay = '$'.$user->hours * $user->hourly_rate;
                }
            
                $user->scheduled_days = $userShifts->count();
                $user->sick = $userShifts->filter(function($value, $key){
                    return $value->attendance == 3;
                })->count();
            
                $user->no_show = $userShifts->filter(function($value, $key){
                    return $value->attendance == 2;
                })->count();
            
                $user->shifts_worked = $user->scheduled_days - ($user->sick + $user->no_show);
                $attendedShifts = $user->scheduled_days - ($user->no_show + $user->sick);
                try{
                    $user->attendance = '%'. round(($attendedShifts/$user->scheduled_days) * 100, 2);
                } catch (\Exception $e) {
                
                }
                return $user;
            });
            //dump($users->count());
            return $users;
        }
        
        public function attendanceRecord ()
        {
            $method = '__fetchAttendanceRecord'.User::roleName();
            $attendance = $this->$method();
            return response()->json($this->fetchAppData($attendance));
        }
        
    }
