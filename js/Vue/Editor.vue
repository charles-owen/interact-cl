<template>
  <div class="cl-interact-editor">
    <p class="cl-prompt">{{prompt}}</p>
    <textarea ref="textarea"></textarea>
  </div>
</template>

<script>
    import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
    import '../../sass/ckeditor/_ck.scss';

    export default {
        props: ['prompt', 'value'],
        mounted() {
            console.log(this.value);
            ClassicEditor
                .create( this.$refs['textarea'], {
                    toolbar: ['bold', 'italic', 'heading', 'undo', 'redo', 'link', 'bulletedList', 'numberedList']
                })
                .then( editor => {
                    this.editor = editor;
                    editor.setData(this.value);

                    editor.model.document.on( 'change:data', ( evt, data ) => {
                        this.$emit('input', editor.getData());
                    });
                } )
                .catch( error => {
                    console.error( error );
                } );
        }

    }

</script>
