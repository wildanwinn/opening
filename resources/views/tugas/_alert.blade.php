@if(session('sukses')) <div class="alert ok">{{ session('sukses') }}</div> @endif
@if(session('error')) <div class="alert err">{{ session('error') }}</div> @endif
@if($errors->any()) <div class="alert err">{{ $errors->first() }}</div> @endif
