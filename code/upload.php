<?php
include_once 'lib/threeds.class.php';
$Threeds = new Threeds();

//if ( !$Threeds->Auth->isLog() )
//	header("Location: /signin");
	
// If the user is not log we create an account for him as soon as he arrives on the page
if	( !$Threeds->Auth->isLog() ) {
	$Threeds->createLazyAccount();
}
	
// we receive a file (simple upload)
if ( isset($_FILES['mpo']) ) {
	if ( $_FILES['mpo']['error']!=UPLOAD_ERR_OK ) {
		// TODO
		// mmh we should report the error to the user
	} else {
		$gid = $Threeds->newImage($_FILES["mpo"]["tmp_name"]);
		if ( $gid===false ) {
			// TODO
			// report the error to the user, the file was uploaded but couldn't be moved
		} else {
			header("Location: /".$gid);
		}
	}
}

// we receive a file (uploadify)
if ( isset($_FILES['Filedata']) ) {
	if ( $_FILES['Filedata']['error']!=UPLOAD_ERR_OK ) {
		exit('Upload Error.');
	} else {
		$gid = $Threeds->newImage($_FILES['Filedata']['tmp_name']);
		if ( $gid===false ) {
			echo '{"result":"Fileformat error"}';
		} else {
			$img = $Threeds->getImageInfo($gid);
			echo '{';
				echo '"result":"ok",';
				echo '"gid":"'.$gid.'",';
				echo '"thumb":"'.$Threeds->getThumbJPS($gid).'",';
				echo '"parallax":"'.$img['parallax'].'"';
			echo '}';
			exit();
		}
		
		exit();
	}
}

	include 'layout/header.php';
?>
<div class="form500"><div class="formBlock" id="dropZone">
<?php
	if ($Threeds->is3DS()) {
?>
	<h2>Upload a picture</h2>
	<form method="post" action="/upload" enctype="multipart/form-data">
	<input type="button" value="Select a picture..."  onclick="$(this).next().click();$(this).prop('value', 'One picture selected');"/>
	<input type="file" name="mpo" style="visibility:hidden;" />
	<input type="submit" value="Upload" />
	</form>
<?php
	} else {
?>
	<h2>Upload multiple pictures</h2>
	<input id="file_upload" name="uploadify_mpo" type="file" multiple="true" style="display:none;">
<?php
	}
?>
</div></div>

<div class="sBlock" id="previewUpload">
</div>

<script type="text/javascript" src="<? echo $versionPath; ?>/lib/uploadifive-v1.0.4-standard/jquery.uploadifive.min.js"></script>
<script type="text/javascript">
function previewUploadedImage(json) {
	upload = jQuery.parseJSON(json);

	if ( upload.result!='ok' )
		return;
	
	$('#previewUpload').append(
		$("<div />", { class: "blockThumb" }).append(
		$("<a />", { href: "/"+upload.gid, target: "_blank"}).append(
		$("<div />", { class: "imgCanvas" }).append(
		$("<div />", { class: "imgCenter" }).append(
		$("<div />", { class: "imgContainer" }).append(
		
		$("<img />", { src: upload.thumb })
			.on('load', function(event) {
				imgWiggle(this, upload.parallax);
			})
	))))));
}

$(function() {
	$('#file_upload').uploadifive({
		'uploadScript' : 'upload',
		'fileSizeLimit' : '1MB',
		'removeCompleted' : true,
		'simUploadLimit'  : 10,
		'onUploadComplete' : function(file, data) {
			previewUploadedImage(data);
		}
	});

});
</script>

<?php
	include 'layout/footer.php';
?>