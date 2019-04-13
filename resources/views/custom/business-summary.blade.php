@extends('nova::layout')

@section('content')
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <div id="business-summary" class="custom-content-container">
        <div class="row">
            <div class="col-sm">
                <h2>
                    {{$business->name}}
                </h2>
                <br>
                <div class="mb-2">
                    <strong>Categories:</strong><br>
                    @foreach($business->categories as $business_category)
                        <div>{{$business_category->name}}</div>
                    @endforeach
                </div>
                <div class="mb-2">
                    <strong>Score:</strong>
                    <span>{{$business->score}}</span>
                </div>
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
            <div class="col-sm">
                <map-box></map-box>
            </div>
        </div>
        <div class="row">
            <div class="col-sm mb-2">
                <strong>Bio:</strong>
                <p>{{$business->bio}}</p>
            </div>
        </div>
        
        <div class="mb-2">
            <p><strong>Attributes:</strong></p>
            <div class="row">
                <div class="col-sm m-2">
                    <table class="table table-striped table-bordered">
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
        </div>
        
        <div class="mb-2">
            <p><strong>Post Images:</strong></p>
            <div class="row">
                @foreach($postImages as $postImage)
                    <div class="col-sm-3 mb-2 text-center">
                        @if($postImage->path)
        <!--                    <img width="250px" height="250px" src="{{Storage::disk('s3')->url($postImage->path) }}" alt="">-->
                            <a class="popup-img-btn" href="#"><img width="250px" height="250px" src="https://img-aws.ehowcdn.com/877x500p/s3-us-west-1.amazonaws.com/contentlab.studiod/getty/f24b4a7bf9f24d1ba5f899339e6949f3" alt=""></a>
                        @else
                            &nbsp;
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="row">
                <div class="col-sm">
                    <div class="float-right">{{ $postImages->appends($_GET)->links("custom.pagination") }}</div>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <p><strong>Reviews:</strong></p>
            <div class="row">
                @foreach($reviews as $review)
                    <div class="col-sm-6 mb-2 ">
                        <div class="card">
                            <div class="card-body">
                                <p class="card-text">{!! nl2br($review->comment) !!}</p>
                                <div class="review-keywords-holder">
                                    @foreach($review->keywords as $keyword)
                                        <div class="float-left p-2">{{$keyword->keyword}}</div>
                                    @endforeach
                                </div>
                                <div class="clearfix"></div>
                                <div class="review-images-holder">
                                    @foreach($review->images as $image)
                                        @if($postImage->path)
                                            <div class="float-left p-2"><img style='max-width: 100px;' src="{{Storage::disk('s3')->url($image->path) }}" alt=""></div>
                                        @else
                                            &nbsp;
                                        @endif
                                        
                                    @endforeach
                                </div>
                                <div class="clearfix"></div>
                                
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
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
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
@endsection
