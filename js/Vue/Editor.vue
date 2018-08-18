<template>
  <div class="cl-interact-editor">
    <p v-if="prompt !== undefined" class="cl-prompt">{{prompt}}</p>
    <textarea ref="textarea" @click="makeActive" @mouseover="makeActive"></textarea>
  </div>
</template>

<script>
    import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
    import '../../sass/ckeditor/_ck.scss';

    export default {
        props: ['prompt', 'value', 'discussion'],
        data: function() {
            return {
                active: false
            }
        },
        mounted() {
            if(this.discussion !== true) {
                this.makeActive();
            }

        },
        methods: {
            makeActive() {
                if(this.active) {
                    return;
                }

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

                this.active = true;
            },
            reset() {
              this.active = false;
              this.editor.destroy();
	            this.$refs['textarea'].value = '';
            }
        }

    }

</script>
