@extends('nova::layout')

@section('content')
<<<<<<< HEAD
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
=======
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="/css/custom.css">

>>>>>>> business_working_hours
    <div id="business-summary" class="custom-content-container">
        <div class="row">
            <div class="col-sm">
                <h2>
                    {{$business->name}}
                </h2>
                <br>
                <div class="mb-2">
                    <div><strong>Working Hours:</strong></div>
                    <ul>
                        @foreach(\App\Models\BusinessWorkingHours::DAYS as $day => $label)
                            <li>
                                <div>{{$label}} :</div>
                                @php
                                    $day_parts = $business->working_hours->where('day', $day);
                                @endphp
                                <ul>
                                @if($day_parts->count() <= 0)
                                    <li>Closed</li>
                                @else
                                    @foreach($day_parts as $day_part)
                                        <li>
                                            <span>{{$day_part->start_time_formatted}}</span>
                                            -
                                            <span>{{$day_part->end_time_formatted}}</span>
                                        </li>
                                    @endforeach
                                @endif
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="mb-2">
                    <div><strong>Categories:</strong></div>
                    @foreach($business->categories as $business_category)
                        <div>
                            <span>{{$business_category->name}}</span>
                            <span>({{$business_category->pivot->relevance}}%)</span>
                        </div>
                    @endforeach
                </div>
                <div class="mb-2">
                    <strong>Score:</strong>
                    <span>{{$business->score}}%</span>
                </div>
<<<<<<< HEAD
                <div class="mb-2">
                    <strong><u>Contacts</u></strong><br>
                    <table width="100%">
                        @foreach($business->contacts as $contact)
                            <tr>
                                <td width="150px">{{$contact->type}}</td>
                                <td>{{$contact->value}}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <div class="col-sm business-map mt-5 mb-3">
                <map-box></map-box>
=======
                @if (count($business->contacts))
                    <div class="mb-2">
                        <strong><u>Contacts</u></strong><br>
                        <table width="100%">
                            @foreach($business->contacts as $contact)
                                <tr>
                                    <td width="150px">{{$contact->type}}</td>
                                    <td>{{$contact->value}}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>
            <div class="col-sm business-map">
                <map-box-detail business-id="{{$business->id}}" lat="{{$business->lat}}"
                                lng="{{$business->lng}}"></map-box-detail>
>>>>>>> business_working_hours
            </div>
        </div>
        <div class="row">
            <div class="col-sm mb-2">
                <strong>Bio:</strong>
                @if ($business->bio)
                    <p>{{$business->bio}}</p>
                @else
                    <span class="content-none">None</span>
                @endif
            </div>
        </div>

        <div class="mb-2">
            <strong>Attributes:</strong>
            @if (count($business->optionalAttributes))
                <div class="row">
                    <div class="col-sm m-2">
                        <table class="table table-striped table-bordered attribute-table">
                            <tr>
                                <th>Attribut Name</th>
                                <th>Attribute Description</th>
                            </tr>
                            @foreach($business->optionalAttributes as $optionalAttribute)
                                <tr>
                                    <td>{{$optionalAttribute->name}}</td>
                                    <td>{{$optionalAttribute->pivot->description}}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            @else
                <span class="content-none">None</span>
            @endif
        </div>

        <div class="mb-2">
<<<<<<< HEAD
            <strong>Top Keywords:</strong>
            @if (count($business->topKeywords()))
                <div class="row">
                    <div class="col-sm m-2">
=======
            <div class="row">
                <div class="col-sm-3 m-2">
                    <strong>Top Keywords:</strong>
                    @if (count($business->topKeywords()))
>>>>>>> business_working_hours
                        <table class="table table-striped table-bordered attribute-table">
                            <tr>
                                <th>Keyword</th>
                                <th>Count</th>
                            </tr>
                            @foreach($business->topKeywords() as $keyword)
                                <tr>
                                    <td>{{$keyword->keyword}}</td>
                                    <td>{{$keyword->cnt}}</td>
                                </tr>
                            @endforeach
                        </table>
<<<<<<< HEAD
                    </div>
                </div>
            @else
                <span class="content-none">None</span>
            @endif
        </div>
        
        <div class="mb-2">
            <strong>Post Images:</strong>
            @if (count($postImages))
                <div class="row">
                    @foreach($postImages as $postImage)
                        <div class="col-sm-3 mb-2 text-center">
                            @if($postImage->path)
                                <a class="popup-img-btn" href="#">
                                    {{-- <img width="250px" height="250px" src="https://img-aws.ehowcdn.com/877x500p/s3-us-west-1.amazonaws.com/contentlab.studiod/getty/f24b4a7bf9f24d1ba5f899339e6949f3" alt="Post Image"> --}}
                                    <img width="250px" height="250px" src="{{ Storage::disk('s3')->url($postImage->path) }}" alt="Post Image">
                                </a>
                            @else
                                &nbsp;
                            @endif
                        </div>
                    @endforeach
                </div>
=======
                    @else
                        <span class="content-none"><br>None</span>
                    @endif
                </div>

                <div class="col-sm-4 m-2">
                    <strong>Top Topics:</strong>
                    @php
                        $topics = $business->getTopics();
                    @endphp
                    @if (count($topics))
                        <table class="table table-striped table-bordered attribute-table">
                            <tr>
                                <th>Topic</th>
                                <th>Count</th>
                                <th>Rating</th>
                            </tr>
                            @foreach($topics as $topic)
                                <tr>
                                    <td>{{$topic['title']}}</td>
                                    <td>{{$topic['total']}}</td>
                                    <td>{{$topic['score']}}%</td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <span class="content-none"><br>None</span>
                    @endif
                </div>
            </div>
        </div>

        @if (count($topics))
            <strong>Topic Details</strong>
            <div class="mb-2" style="max-height: 400px; overflow-y: scroll; overflow-x: hidden;">
                <div class="row">
                    @foreach($topics as $topic)
                        <div class="col-sm-4">
                            <strong>{{$topic['title']}}:</strong>
                            <table class="table table-striped table-bordered attribute-table">
                                <tr>
                                    <th>Phrase</th>
                                    <th>Count</th>
                                </tr>
                                @foreach($topic['phrases'] as $pr)
                                    <tr>
                                        <td>{{$pr['keyword']}}</td>
                                        <td>{{$pr['cnt']}}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    @endforeach
                </div>
            </div> <!-- /story-details -->
        @endif

        <div class="mb-2">
            <strong>Post Images:</strong>
            @if (count($postImages))
                <table id="post-images-table" class="table table-bordered table-striped table-condensed dataTable"
                       data-business-id="{{$business->id}}">
                    <thead>
                    <tr>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

>>>>>>> business_working_hours
            @else
                <span class="content-none">None</span>
            @endif
            <div class="row">
                <div class="col-sm">
                    <div class="float-right">{{ $postImages->appends($_GET)->links("custom.pagination") }}</div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <strong>Reviews:</strong>
            @if (count($reviews))
<<<<<<< HEAD
                <div class="row">
                    @foreach($reviews as $review)
                        <div class="col-sm-6 mb-2 ">
                            <div class="card">
                                <div class="card-body">
                                    <div class="review-images-holder">
                                        @foreach($review->images as $image)
                                            @if($postImage->path)
                                                <a class="popup-img-btn" href="#">
                                                    <img width="250px" height="250px" src="{{ Storage::disk('s3')->url($image->path) }}" class="review-image mb-1" alt="Review Image">
                                                </a>
                                            @else
                                                &nbsp;
                                            @endif
                                        @endforeach
                                    </div>
                                    <div class="clearfix"></div>
                                    <p class="card-text">{!! nl2br($review->comment) !!}</p>
                                    <div class="review-keywords-holder">
                                        @foreach($review->keywords as $keyword)
                                            <div class="float-left p-2 card-items">{{$keyword->keyword}}</div>
                                        @endforeach
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
=======
                <table id="reviews-table" class="table table-bordered table-striped table-condensed dataTable"
                       data-business-id="{{$business->id}}">
                    <thead>
                    <tr>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

>>>>>>> business_working_hours
            @else
                <span class="content-none">None</span>
            @endif
            <div class="row">
                <div class="col-sm">
                    <div class="float-right">{{ $reviews->appends($_GET)->links("custom.pagination") }}</div>
                </div>
            </div>
        </div>

        <div id="ImageModal" class="modal fade " tabindex="-1" role="dialog">
            <div class="modal-dialog modal-full">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="//placehold.it/1000x600" class="view-img ">
                    </div>
                </div>
            </div>
        </div>
        <loading ref="loading"></loading>
    </div>
<<<<<<< HEAD
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
=======
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
            integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
            crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
            integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
            crossorigin="anonymous"></script>
    <script type="text/javascript" charset="utf8"
            src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>

    <script src="{{asset('js/summary-custom.js')}}"></script>
>>>>>>> business_working_hours
@endsection
