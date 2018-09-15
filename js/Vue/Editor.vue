<template>
  <div class="cl-interact-editor">
    <p v-if="prompt !== undefined" class="cl-prompt">{{prompt}}</p>
    <textarea ref="textarea" @click="makeActive" @mouseover="makeActive"></textarea>
  </div>
</template>

<script>
    import ClassicEditor from 'ckeditor5-cl';
    import '../../sass/ckeditor/_ck.scss';

    export default {
        props: ['prompt', 'value', 'discussion', 'canned'],
        data: function() {
            return {
                active: false,
                editor: null
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

                let toolbar = ['bold', 'italic', 'heading', 'undo', 'redo', 'link', 'bulletedList', 'numberedList'];
                if(this.canned !== undefined) {
                	toolbar.push('canned');
                }

                let options = {
                	toolbar: toolbar
                }

                if(this.canned !== undefined) {
                  options.canned = {
                  	options: this.canned
                  }
                }

                ClassicEditor
                    .create( this.$refs['textarea'], options)
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
              if(this.editor !== null) {
	              this.editor.destroy();
	              this.editor = null;
              }

	            this.$refs['textarea'].value = '';
            }
        }

    }

</script>
