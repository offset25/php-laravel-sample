<?php
    
    namespace App\Http\Controllers;
    
    use App\Helpers\ShiftsHelper;
    use App\Mail\ShiftAvailable;
    use App\Notifications\ManagerShiftApproval;
    use App\Notifications\ShiftModified;
    use App\Notifications\ShiftRequestAccepted;
    use App\Notifications\ShiftRequestRejected;
    use App\Notifications\ShiftScheduled;
    use App\Notifications\ShiftTrade;
    use App\Shift;
    use App\User;
    use App\UserRequest;
    use Carbon\Carbon;
    use function Clue\StreamFilter\fun;
    use function foo\func;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Support\Facades\Notification;
    use Illuminate\Notifications\ChannelManager;
    
    class ShiftsController extends Controller
    {
        public $copyShiftConfig;
        public $apiConfig;
        
        public function __construct ()
        {
            parent::__construct();
            $this->middleware('auth');
            
            $this->copyShiftConfig = [
                'attendance' => [
                    'days_to_copy' => 0,
                    'need_user' => [
                        'user_id' => ''
                    ]
                ],
                'scheduler' => [
                    'days_to_copy' => 7,
                    'need_user' => [
                    
                    ]
                ]
            ];
            
            $this->apiConfig = [
                'manager' => [
                    'fetchApiHandler' => 'fetchManagerShifts'
                ],
                'admin' => [
                    'fetchApiHandler' => 'fetchAdminShifts'
                ],
                'employee' => [
                    'fetchApiHandler' => 'fetchEmployeeShifts'
                ]
            ];
        }
        
        private function fetchAdminShifts ()
        {
            $params = [
                'company_id' => auth()->user()->company_id
            ];
            
            $params = array_merge($params, array_filter(request()->all()));
            $shifts = Shift::with('department', 'location')
                ->join('users', 'users.id', '=', 'shifts.user_id', 'LEFT')
                ->where('shifts.company_id', Auth::user()->company_id);
            
            if (isset($params['department_id']) && !empty($params['department_id'])) {
                $shifts = $shifts->join('department_user', 'users.id', '=', 'department_user.user_id', 'LEFT');
                $p = $params['department_id'];
                if ($p) {
                    $shifts->where(
                        function ($query) use ($p) {
                            foreach ($p as $dept) {
                                $query->orWhere('department_user.department_id', $dept);
                                $query->orWhere('users.department_id', $dept);
                            }
                        });
                }
            }
            if (isset($params['location_id']) && !empty($params['location_id'])) {
                $shifts = $shifts->join('location_user', 'users.id', '=', 'location_user.user_id', 'LEFT');
                $p = $params['location_id'];
                $shifts->where(
                    function ($query) use ($p) {
                        foreach ($p as $loc) {
                            $query->orWhere('location_user.location_id', $loc);
                            $query->orWhere('users.location_id', $loc);
                        }
                    });
            }
            if (isset($params['job_tag_id']) && !empty($params['job_tag_id'])) {
                $shifts = $shifts->join('job_tag_user', 'users.id', '=', 'job_tag_user.user_id', 'LEFT');
                $p = $params['job_tag_id'];
                $shifts->where(
                    function ($query) use ($p) {
                        foreach ($p as $jobTag) {
                            $query->orWhere('job_tag_user.job_tag_id', $jobTag);
                            $query->orWhere('users.job_tag_id', $jobTag);
                        }
                    });
            }
            if (isset($params['employee_id']) && !empty($params['employee_id'])) {
                $p = $params['employee_id'];
                $shifts->where(
                    function ($query) use ($p) {
                        foreach ($p as $employeeId) {
                            $query->orWhere('users.id', $employeeId);
                        }
                    });
            }
            
            $shifts = $shifts->select('shifts.*')
                ->where('attendance', '!=', 3)
                ->groupBy('shifts.id');
            //dump($shifts->toSql());
            //dd($shifts->get());
            $shifts = $shifts->get();
            //transforming results.
            $shifts->transform(
                function ($shift) {
                    
                    $shift->employeeName = '';
                    $shift->empDepartment = '';
                    $shift->empLocation = '';
                    if (isset($shift->user)) {
                        $shift->employeeName = $shift->user->first_name . ' ' . $shift->user->last_name;
                        $shift->empDepartment = $shift->user->department->name;
                        $shift->empLocation = $shift->user->location->title;
                        $shift->userSecondaryDepartments = $shift->user->secondaryDepartments;
                        $shift->userSecondaryLocations = $shift->user->secondaryLocations;
                        $shift->userSecondaryJobTags = $shift->user->secondaryJobTags;
                    }
                    
                    $shift->jobTag = $shift->jobTag->title;
                    return $shift;
                });
            return $shifts;
        }
        
        private function fetchManagerShifts ()
        {
            $params = [
                'company_id' => auth()->user()->company_id
            ];
            
            $params = array_merge($params, array_filter(request()->all()));
            $shifts = Shift::with('department', 'location')
                ->join('users', 'users.id', '=', 'shifts.user_id', 'LEFT')
                ->where('shifts.company_id', Auth::user()->company_id);
            
            if (isset($params['job_tag_id']) && !empty($params['job_tag_id'])) {
                $shifts = $shifts->join('job_tag_user', 'users.id', '=', 'job_tag_user.user_id');
                $p = $params['job_tag_id'];
                $shifts->where(
                    function ($query) use ($p) {
                        foreach ($p as $jobTag) {
                            $query->orWhere('job_tag_user.job_tag_id', $jobTag);
                            $query->orWhere('users.job_tag_id', $jobTag);
                        }
                    });
            }
            if (isset($params['employee_id']) && !empty($params['employee_id'])) {
                $p = $params['employee_id'];
                $shifts->where(
                    function ($query) use ($p) {
                        foreach ($p as $employeeId) {
                            $query->orWhere('users.id', $employeeId);
                        }
                    });
            }
            
            $shifts = $shifts->select('shifts.*')
                ->where(
                    function ($query) {
                        $query->where('shifts.location_id', User::locId());
                        $query->orWhere('shifts.department_id', User::deptId());
                        return $query;
                    })
                ->where('attendance', '!=', 3)
                ->groupBy('shifts.id');
            $shifts = $shifts->get();
            
            //transforming results.
            $shifts->transform(
                function ($shift) {
                    $shift->employeeName = '';
                    $shift->empDepartment = '';
                    $shift->empLocation = '';
                    $shift->jobTag = $shift->jobTag->title;
                    
                    if (isset($shift->user)) {
                        $shift->employeeName = $shift->user->first_name . ' ' . $shift->user->last_name;
                        $shift->empDepartment = $shift->user->department->name;
                        $shift->empLocation = $shift->user->location->title;
                        $shift->userSecondaryDepartments = $shift->user->secondaryDepartments;
                        $shift->userSecondaryLocations = $shift->user->secondaryLocations;
                        $shift->userSecondaryJobTags = $shift->user->secondaryJobTags;
                        
                        if($shift->user->user_type == 2 && $shift->user->id != User::userId() )
                            return $shift;
                    }else {
                        return $shift;
                    }
                });
    
            $newFilteredShifts = collect();
            $shifts = $shifts->reject(function ($shift){
                    return $shift == null;
            });
    
            $shifts->transform(
                function ($shift, $key) use($newFilteredShifts) {
                    $newFilteredShifts[] = $shift;
                    return $shift;
                });
            return $newFilteredShifts;
        }
        
        private function fetchEmployeeShifts ()
        {
            $params = [
                'company_id' => auth()->user()->company_id
            ];
            
            $params = array_merge($params, array_filter(request()->all()));
            $shifts = Shift::select('shifts.*')
                ->with('department', 'location', 'user')
                ->where('shifts.is_made_live', 'Y')
                ->where('shifts.company_id', Auth::user()->company_id);
            
            if (isset($params['employee_id']) && !empty($params['employee_id'])) {
                $shifts = $shifts->join('users', 'users.id', '=', 'shifts.user_id');
                $p = $params['employee_id'];
                $shifts->where('users.id', $p);
            } elseif (isset($params['colleagues_id']) && !empty($params['colleagues_id'])) {
                $users = User::where('location_id', auth()->user()->location_id)
                    ->where('job_tag_id', auth()->user()->job_tag_id)
                    ->where('id', '!=', auth()->id())
                    ->get();
                
                if ($users) {
                    $shifts->where(
                        function ($query) use ($users) {
                            foreach ($users as $user) {
                                $query->orWhere('user_id', $user->id);
                            }
                        });
                }
            } else {
                $shifts = $shifts
                    ->where(
                        function ($query) {
                            $query->where('shifts.user_id', \auth()->user()->id);
                            $query->orWhere('shifts.user_id', '0');
                        })
                    ->where(
                        function ($query) {
                            $query->where('shifts.location_id', \auth()->user()->location_id);
                            $query->orWhere('shifts.job_tag_id', \auth()->user()->job_tag_id);
                        });
            }
            //dump($shifts->toSql());
            $shifts = $shifts->groupBy('shifts.id')->get();
            //dd($shifts);
            //transforming results.
            $shifts->transform(
                function ($shift) {
                    $shift->employeeName = '';
                    $shift->empDepartment = '';
                    $shift->empLocation = '';
                    if (isset($shift->user)) {
                        $shift->employeeName = $shift->user->first_name . ' ' . $shift->user->last_name;
                        $shift->empDepartment = $shift->user->department->name;
                        $shift->empLocation = $shift->user->location->title;
                        $shift->userSecondaryDepartments = $shift->user->secondaryDepartments;
                        $shift->userSecondaryLocations = $shift->user->secondaryLocations;
                        $shift->userSecondaryJobTags = $shift->user->secondaryJobTags;
                    }
                    
                    $shift->jobTag = $shift->jobTag->title;
                    return $shift;
                });
            return $shifts;
        }
        
        public function shiftsApi ()
        {
            $userRole = \auth()->user()->role->name;
            $handler = $this->apiConfig[$userRole]['fetchApiHandler'];
            $shifts = $this->$handler();
            return response()->json($this->fetchAppData($shifts));
        }
        
        public function store (Request $request)
        {
            //creating shift data
            $employee = '';
            $shiftData = [
                'company_id' => auth()->user()->company_id,
                'department_id' => User::roleName() == 'admin' ? $request['department_id'] : User::deptId(),
                'job_tag_id' => $request['job_tag_id'],
                'location_id' => User::roleName() == 'admin' ? $request['location_id'] : User::locId(),
                'shift_notes' => 'Shift Created',
                'start_time' => $request['start_time'],
                'end_time' => $request['end_time'],
                'hourly_rate' => $request->has('hourly_rate') ? ShiftsHelper::cleanRate($request['hourly_rate']) : '0'
            ];
            if ($request->has('user_id')) {
                $employee = User::find($request['user_id']);
                $shiftData = array_merge(
                    $shiftData, [
                    'user_id' => $employee->id,
                    'employee_start_time' => ShiftsHelper::fetchEmployeeTime($employee->time_zone, $request['start_time']),
                    'employee_end_time' => ShiftsHelper::fetchEmployeeTime($employee->time_zone, $request['end_time'])
                ]);
            }
            //dd($shiftData);
            $shift = Shift::create($shiftData);
            
            //sending notification to employee.
            if ($request->has('user_id')) {
                $content = 'Your schedule is available for {{start_date_time}} - {{end_date_time}} please click here to view';
                $this->__sendNotification($shift, $employee, $content);
            }
            
            return response()->json(
                [
                    'status' => true,
                    'message' => 'done',
                    'data' => [
                        'shiftId' => $shift->id
                    ]
                ]);
        }
        
        private function __sendNotification ($shift, $users, $notificationBody, $mode = 'create')
        {
            $url = '/view-shift/{{user_id}}';
            $details = [
                'greeting' => 'Hey {{user_name}}',
                'body' => $notificationBody, //'Your schedule is available for {{start_date_time}} - {{end_date_time}} please click here to view',
                'thanks' => 'Thanks for using GoToShift!',
                'actionText' => 'View shift ',
                
                'actionURL' => $url,
                'shift_id' => $shift->id,
                'shiftData' => $shift
            ];
            
            if ($mode == 'create') {
                Notification::send($users, new ShiftScheduled($details));
            } else {
                Notification::send($users, new ShiftModified($details));
            }
            return true;
        }
        
        private function __copy ($shiftId, $ref = 'scheduler')
        {
            if ($shiftId) {
                $shift = Shift::find($shiftId);
                $numDaysToCopy = $this->copyShiftConfig[$ref]['days_to_copy'];
                $copiedShift = $this->__prepareShiftData($shift, $numDaysToCopy);
                
                $copiedShift = array_merge($copiedShift, $this->copyShiftConfig[$ref]['need_user']);
                return Shift::create($copiedShift);
            }
            return false;
        }
        
        public function copyShift (Request $request)
        {
            $newShift = $this->__copy($request['shiftId']);
            return response()->json(['status' => true, 'message' => 'done', 'shiftId' => $newShift->id]);
        }
        
        private function __prepareShiftData ($shift, $daysToAdd = 7)
        {
            $startTime = ShiftsHelper::addDaysToDate($shift->start_time, $daysToAdd);
            $endTime = ShiftsHelper::addDaysToDate($shift->end_time, $daysToAdd);
            $empStartTime = ShiftsHelper::addDaysToDate($shift->employee_start_time, $daysToAdd);
            $empEndTime = ShiftsHelper::addDaysToDate($shift->employee_end_time, $daysToAdd);
            
            $copiedShift = [
                'user_id' => $shift->user_id,
                'company_id' => $shift->company_id,
                'department_id' => $shift->department_id,
                'job_tag_id' => $shift->job_tag_id,
                'location_id' => $shift->location_id,
                'hourly_rate' => ShiftsHelper::cleanRate($shift->hourly_rate),
                'shift_notes' => $shift->shift_notes,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'employee_start_time' => $empStartTime,
                'employee_end_time' => $empEndTime,
                'attendance' => $shift->attendance
            ];
            
            return $copiedShift;
        }
        
        public function copyAllShifts (Request $request)
        {
            $weekDate = $request['date'];
            $selectedWeekDate = Carbon::parse($weekDate);
            $weekNumber = $selectedWeekDate->format('W');
            $dateRange = ShiftsHelper::getStartAndEndDate($weekNumber, date('Y'));
            $model = new Shift();
            $shiftsData = $model->getShiftsByDateRange($dateRange);
            $copiedShifts = [];
            foreach ($shiftsData as $shift) {
                $copiedShifts[] = $this->__prepareShiftData($shift);
            }
            //dd($copiedShifts);
            Shift::insert($copiedShifts);
            return response()->json(['status' => true, 'message' => 'done']);
        }
        
        public function getShift (Shift $shift)
        {
            return response()->json($shift);
        }
        
        private function __markAttended ($shiftId = null)
        {
            $keys = array_keys(ShiftsHelper::getAttendanceStatus());
            return $keys[0];
        }
        
        private function __markNoShow ($shiftId)
        {
            $this->__copy($shiftId, 'attendance');
            $keys = array_keys(ShiftsHelper::getAttendanceStatus());
            return $keys[1];
        }
        
        private function __markSick ($shiftId)
        {
            $this->__copy($shiftId, 'attendance');
            $keys = array_keys(ShiftsHelper::getAttendanceStatus());
            return $keys[2];
        }
        
        private function markAttendance ($attendance, $shiftId)
        {
            $attStatus = ShiftsHelper::getAttendanceStatus();
            $method = '__mark' . $attStatus[$attendance];
            return $this->$method($shiftId);
        }
        
        public function update (Shift $shift, Request $request)
        {
            $this->markAttendance($request['attendance'], $shift->id);
            $shiftData = [
                'user_id' => $request['user_id'],
                'start_time' => $request['start_time'],
                'end_time' => $request['end_time'],
                'department_id' => User::roleName() == 'admin' ? $request['department_id'] : User::deptId(),
                'job_tag_id' => $request['job_tag_id'],
                'location_id' => User::roleName() == 'admin' ? $request['location_id'] : User::locId(),
                'attendance' => $request['attendance'],
                'hourly_rate' => ShiftsHelper::cleanRate($request['hourly_rate']),
            ];
            
            //sending notification to employee.
            if ($request->has('user_id') && $request['user_id'] > 0) {
                $employee = User::find($request['user_id']);
                $shiftData = array_merge(
                    $shiftData, [
                    'user_id' => $employee->id,
                    'employee_start_time' => ShiftsHelper::fetchEmployeeTime($employee->time_zone, $request['start_time']),
                    'employee_end_time' => ShiftsHelper::fetchEmployeeTime($employee->time_zone, $request['end_time'])
                ]);
                
                //$shiftDay = Carbon::parse($shiftData['employee_start_time'])->format('l');
                //$shiftDate = Carbon::parse($shiftData['employee_start_time'])->format('Y-m-d');
                
                $oldShift = $shift->replicate();
                $oldShift->old_start_time = ShiftsHelper::fetchEmployeeTime($employee->time_zone, $shift->start_time);
                $oldShift->old_end_time = ShiftsHelper::fetchEmployeeTime($employee->time_zone, $shift->end_time);
                $oldShift->new_start_time = $shiftData['employee_start_time'];
                $oldShift->new_end_time = $shiftData['employee_end_time'];
                
                $content = "Shift for {{old_start_date_time}} - {{old_end_date_time}} has been changed to {{new_start_date_time}} - {{new_end_date_time}}
                Please click here to view";
                
                if ($shift->is_made_live == 1) {
                    $shiftData['is_notified'] = 1;
                    $this->__sendNotification($oldShift, $employee, $content, 'update');
                }
            }
            
            $shift->update($shiftData);
            return response()->json(['status' => true, 'message' => 'done']);
        }
        
        public function delete (Shift $shift)
        {
            return response()->json($shift->delete());
        }
        
        public function sendNotifications (Request $request)
        {
            $msg = 'Notification sent successfully!';
            $status = true;
            $shift = Shift::find($request['id']);
            $users = User::where('company_id', \auth()->user()->company_id)
                ->select('id', 'first_name', 'last_name', 'email', 'avatar', 'phone')
                ->where('users.user_type', '2')
                ->where('job_tag_id', $request['job_tag_id'])
                ->get();
            
            //dd($users,\auth()->user()->company_id);
            $url = url('/claim-shift/' . encrypt($shift->id) . '/{{user_id}}');
            $details = [
                'greeting' => 'Hey {{user_name}}',
                'body' => 'There is an unclaimed shift from start {{start_date_time}} to end {{end_date_time}}. Click on this link to claim it.',
                'thanks' => 'Thanks for using GoToShift!',
                'actionText' => 'Claim shift',
                
                'actionURL' => $url,
                'order_id' => 101,
                'shiftData' => $shift
            ];
            
            if ($users->count()) {
                Notification::send($users, new \App\Notifications\ShiftAvailable($details));
            } else {
                $status = false;
                $msg = 'No employees with this tag available!';
            }
            return response()->json(
                [
                    'status' => $status,
                    'msg' => $msg
                ]);
        }
        
        public function confirmTrading (Request $request)
        {
            $url = '/claim-shift/' . '' . '/{{user_id}}';
            $tradingShifts = Shift::with('user')->find($request['shifts']);
            $reqShiftData = Shift::with('user')->find($request['target_shift']);
            $tradingUsers = $tradingShifts->user;
            
            //notification body.
            $details = [
                'greeting' => 'Hey {{user_name}}',
                'body' => 'Your co-worker {{colleague_name}} wants to trade their shift from {{target_shift_start_from}} to {{target_shift_end_to}} with your shift {{trading_shift_start_time}}  -- {{trading_shift_end_to}}. Please click below links to accept or reject request',
                'thanks' => 'Thanks for using GoToShift!',
                
                'actionText' => 'This is an action URL',
                'actionURL' => $url,
                
                'actionAcceptText' => 'Accept trade',
                'actionAcceptUrl' => $url,
                
                'actionRejectText' => 'Reject trade',
                'actionRejectUrl' => $url,
                
                'shift_id' => $request['target_shift'],
                'tradingShifts' => $tradingShifts,
                'reqShiftData' => $reqShiftData
            ];
            
            //sending notification.
            Notification::send($tradingUsers, new ShiftTrade($details));
            
            //updating shift status.
            $reqShiftData->update(['is_requested' => '1']);
            
            //creating request
            UserRequest::create(
                [
                    'user_id' => $tradingUsers->id,
                    'request_type' => '1',
                    'request_desc' => 'User requested a shift trade.'
                ]);
            
            return response()->json(
                [
                    'status' => 'true',
                    'message' => 'Request submitted successfully!'
                ]);
        }
        
        public function approveShift (Request $request, $shiftId, $userId, $targetShiftId)
        {
            $shift = Shift::find(decrypt($shiftId));
            $targetShift = Shift::find(decrypt($targetShiftId));
            
            $type = 'success';
            $message = 'You have accepted offer. Please login to view your shift\'s detail. Thanks!';
            if ($shift->is_requested == 1) {
                
                $requester = $shift->user_id;
                $acceptor = decrypt($userId);
                
                $acceptor = User::with('company')->find($acceptor);
                $requester = User::with('company')->find($requester);
                //dd($requester->company['auto_approve_shift_trade']);
                if ($requester->company['auto_approve_shift_trade']) {
                    $details = [
                        'greeting' => 'Hey {{user_name}}',
                        'body' => 'Your co-worker {{colleague_name}} has accepted your offer for trade of the following
                shift {{target_shift_start_from}} to {{target_shift_end_to}}',
                        'thanks' => 'Thanks for using GoToShift!',
                        'shift_id' => $shiftId,
                        'acceptor' => $acceptor,
                        'shiftData' => $shift,
                        'requester' => $requester
                    ];
                    
                    Notification::send($requester, new ShiftRequestAccepted($details));
                    
                    $targetShift->user_id = $shift->user_id;
                    $targetShift->save();
                    
                    //updating shift.
                    $shift->is_requested = 0;
                    $shift->user_id = $acceptor->id;
                    $shift->save();
                    
                } else {
                    //dd($requester->manager);
                    $acceptor = decrypt($userId);
                    $acceptor = User::with('company')->find($acceptor);
                    $message = 'We have notified your manager. Once request is approved, the shift will be traded. Thanks!';
                    $details = [
                        'greeting' => 'Hey {{user_name}}',
                        'body' => 'Your employee {{employee_name}} has requested for trade of the following shift {{target_shift_start_from}} to {{target_shift_end_to}}',
                        'thanks' => 'Thanks for using GoToShift!',
                        'shift_id' => $shiftId,
                        'acceptor' => $requester->manager,
                        'shiftData' => $shift,
                        'targetShift' => $targetShift,
                        'requester' => $requester,
                        'requestedEmployee' => $acceptor
                    ];
                    
                    Notification::send($requester->manager, new ManagerShiftApproval($details));
                }
                
            } else {
                $type = 'danger';
                $message = 'We\'re sorry, this shift has been accepted by someone else!';
            }
            return view(
                'pages.index', [
                'type' => $type,
                'message' => $message
            ]);
            
        }
        
        public function rejectShift (Request $request, $shiftId, $userId)
        {
            $shift = Shift::find(decrypt($shiftId));
            //dd($shift);
            $type = 'success';
            $message = 'You have rejected trade offer. Thanks for your feedback.';
            
            if ($shift->is_requested == 1) {
                $requester = $shift->user_id;
                $rejector = decrypt($userId);
                $rejector = User::find($rejector);
                $requester = User::find($requester);
                $details = [
                    'greeting' => 'Hey {{user_name}}',
                    'body' => 'Your co-worker {{colleague_name}} has accepted your request for trade of the following
                    shift {{target_shift_start_from}} to {{target_shift_end_to}}',
                    'thanks' => 'Thanks for using GoToShift!',
                    'shift_id' => $shiftId,
                    'rejector' => $rejector,
                    'shiftData' => $shift,
                    'requester' => $requester
                ];
                
                Notification::send($requester, new ShiftRequestRejected($details));
                
            } else {
                $type = 'danger';
                $message = 'We\'re sorry, this shift has been accepted by someone else!';
            }
            return view(
                'pages.index', [
                'type' => $type,
                'message' => $message
            ]);
        }
        
        public function managerApproveShift (Request $request, $shiftId, $userId, $acceptorId, $targetShiftId, $managerId)
        {
            $shift = Shift::find(decrypt($shiftId));
            $targetShift = Shift::find(decrypt($targetShiftId));
            
            $type = 'success';
            $message = 'Thanks for taking action. Employee will be notified!';
            if ($shift->is_requested == 1) {
                $requester = $shift->user_id;
                $acceptorId = decrypt($acceptorId);
                
                $acceptor = User::with('company')->find($acceptorId);
                $requester = User::with('company')->find($requester);
                
                //dd($shift, $targetShift, $requester, $acceptor);
                
                $details = [
                    'greeting' => 'Hey {{user_name}}',
                    'body' => 'Your manager has accepted your shift trade. Your co-worker {{colleague_name}} has also accepted your offer for trade of the following
                shift {{target_shift_start_from}} to {{target_shift_end_to}}',
                    'thanks' => 'Thanks for using GoToShift!',
                    'shift_id' => $shiftId,
                    'acceptor' => $acceptor,
                    'shiftData' => $shift,
                    'targetShift' => $targetShift,
                    'requester' => $requester
                ];
                
                Notification::send($requester, new ShiftRequestAccepted($details));
                Notification::send($acceptor, new ShiftRequestAccepted($details));
                
                //updating target shift.
                $targetShift->user_id = $shift->user_id;
                $targetShift->save();
                
                //updating shift.
                $shift->is_requested = 0;
                $shift->user_id = $acceptorId;
                $shift->save();
                
            } else {
                $type = 'danger';
                $message = 'We\'re sorry, this shift has been accepted by someone else!';
            }
            return view(
                'pages.index', [
                'type' => $type,
                'message' => $message
            ]);
        }
        
        public function managerRejectShift (Request $request, $shiftId, $userId, $acceptorId, $managerId)
        {
            $shift = Shift::find(decrypt($shiftId));
            $type = 'success';
            $message = 'You have rejected trade offer. Employee will be notified. Thanks for your feedback.';
            if ($shift->is_requested == 1) {
                $requesterId = $shift->user_id;
                $rejectorId = decrypt($userId);
                $rejector = User::find($rejectorId);
                $acceptorId = decrypt($acceptorId);
                $requester = User::find($requesterId);
                $acceptor = User::find($acceptorId);
                
                //dd($requester, $acceptorId, decrypt($managerId));
                $details = [
                    'greeting' => 'Hey {{user_name}}',
                    'body' => 'Your manager has rejected your request for trade of the following
                    shift {{target_shift_start_from}} to {{target_shift_end_to}}',
                    'thanks' => 'Thanks for using GoToShift!',
                    'shift_id' => $shiftId,
                    'rejector' => $rejector,
                    'shiftData' => $shift,
                    'requester' => $requester
                ];
                
                Notification::send($requester, new ShiftRequestRejected($details));
                Notification::send($acceptor, new ShiftRequestRejected($details));
                
            } else {
                $type = 'danger';
                $message = 'We\'re sorry, this shift has been accepted by someone else!';
            }
            return view(
                'pages.index', [
                'type' => $type,
                'message' => $message
            ]);
        }
    }
