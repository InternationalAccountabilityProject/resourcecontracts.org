@extends('layout.app')

@section('content')
	<div class="panel panel-default">
		<div class="panel-heading">@lang('contract.all_contract')
			<div class="pull-right" role="group" aria-label="...">
				<?php
				$url = Request::all();
				$url['download'] = 1;
				?>
				@if(!empty($download_files))
					<div class="btn-group">
						<a href="#" class="btn btn-default
                        dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							@lang('global.text_download') <span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							@foreach($download_files as $key => $file)
								<li>
									<a href="{{route('bulk.text.download', $file['path'])}}">
										{{$key}}
										<div>
											<small>({{$file['size']}})</small>
										</div>
									</a>
								</li>
								@if(in_array($key,['OLC', 'All']))
									<li role="separator" class="divider"></li>
								@endif
							@endforeach
							<li role="separator" class="divider"></li>
							<li>
								<small style="padding: 10px;">Updated on {{$file['date']}}</small>
							</li>
						</ul>
					</div>
				@endif
				<a href="{{route("contract.index",$url)}}" class="btn btn-info">@lang('contract.download')</a>
				<a href="{{route('contract.import')}}" class="btn btn-default">@lang('contract.import.name')</a>
				<a href="{{route('contract.select.type')}}" class="btn btn-primary btn-import">@lang('contract.add')</a>
			</div>
		</div>

		<div class="panel-body contract-filter">
			{!! Form::open(['route' => 'contract.index', 'method' => 'get', 'class'=>'form-inline']) !!}
			{!! Form::select('year', ['all'=>trans('contract.year')] + $years , Input::get('year') , ['class' =>
			'form-control']) !!}

			{!! Form::select('country', ['all'=>trans('contract.country')] + $countries , Input::get('country') ,
			['class' =>'form-control']) !!}

			{!! Form::select('category', ['all'=>trans('contract.category')] + config('metadata.category'),
			Input::get('category') ,
			['class' =>'form-control']) !!}

			{!! Form::select('resource', ['all'=>trans('contract.resource')] + trans_array($resources,
			'codelist/resource') ,
			Input::get
			('resource') ,
			['class' =>'form-control']) !!}
			{!! Form::text('q', Input::get('q') , ['class' =>'form-control','placeholder'=>trans('contract.search_contract')]) !!}

			{!! Form::submit(trans('contract.search'), ['class' => 'btn btn-primary']) !!}
			{!! Form::close() !!}
			<br/>
			<br/>
			<table class="table table-contract table-responsive contract-table">
				@forelse($contracts as $contract)
					<tr>
						<td width="65%">
							<i class="glyphicon glyphicon-file"></i>
							<a href="{{route('contract.show', $contract->id)}}"
							   class="contract-title">{{$contract->metadata->contract_name or $contract->metadata->project_title}}</a>
							<span class="label label-default">
								<?php echo strtoupper(
										$contract->metadata->language
								);?>
							</span>
							@if($contract->metadata_status == \App\Nrgi\Entities\Contract\Contract::STATUS_PUBLISHED)
								<span class="published">
								<i class="glyphicon glyphicon-ok"></i>
									@lang('contract.published')
							</span>
							@endif
							<div class="contract-info-list">
								<span class="info">
									<i class="glyphicon glyphicon-time"></i>
									{{$contract->metadata->signature_year}}
								</span>
								<span class="info">
									<i class="glyphicon glyphicon glyphicon-map-marker"></i>
									{{$contract->metadata->country->name}}
								</span>
								<span class="info">
									<i class="glyphicon glyphicon-comment"></i>
									{{$contract->annotations->count()}}
								</span>
							</div>
						</td>
						<td align="right">
							<div class="contract-extra-details">
								<span>{{getFileSize($contract->metadata->file_size)}}</span>
								<span><?php echo $contract->createdDate('M d, Y');?></span>
							</div>
							<div class="contract-metadata-lang">
								@lang('contract.translation_available_in')
								<span class="index-lang">
									@include('contract.partials.form.language', ['view' => 'show', 'page'=>'index'] )
								</span>
							</div>
						</td>
					</tr>

				@empty
					<tr>
						<td colspan="2">@lang('contract.contract_not_found')</td>
					</tr>
				@endforelse

			</table>
			@if ($contracts->lastPage()>1)
				<div class="text-center paginate-wrapper">
					<div class="pagination-text">@lang('contract.showing') {{($contracts->currentPage()==1)?"1":($contracts->currentPage()-1)*$contracts->perPage()}} @lang('contract.to') {{($contracts->currentPage()== $contracts->lastPage())?$contracts->total():($contracts->currentPage())*$contracts->perPage()}} @lang('contract.of') {{$contracts->total()}} @lang('contract.contract')</div>
					{!! $contracts->appends($app->request->all())->render() !!}
				</div>
			@endif
		</div>
	</div>
@endsection
@section('script')
	<link href="{{asset('css/select2.min.css')}}" rel="stylesheet"/>
	<script src="{{asset('js/select2.min.js')}}"></script>
	<script type="text/javascript">
		var lang_select = '@lang('global.select')';
		$('select').select2({placeholder: lang_select, allowClear: true, theme: "classic"});
	</script>
@stop