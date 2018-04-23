<template>
    <div>
        <li class="list-group-item iccommand-thumb-container" :class="{'image-deleted-border': isImageDeleted, 'image-edited-border': isImageEdited}">
            <div v-if="isImageEditedError || isImageDeletedError" class="alert alert-danger" role="alert">
                <p>{{ actionType }} failed! Please try again.</p>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div v-if="isEditMode" class="iccommand-thumb-hamburger">
                      <i class="fa fa-bars"></i>
                    </div>
                    <div class="iccommand-thumb-image">
                      <img width="103px" height="103px" :src="uploadsThumbnailURL + image.path" :alt="image.name" />
                    </div>
                    <button v-if="isEditMode" type="button" class="btn btn-sm btn-danger pull-right" @click="openDeleteImageModal(image)"><i class="fa fa-times" aria-hidden="true"></i></button>
                    <h6 class="box-title heading-primary"><i v-if="this.$vnode.key == 0" class="fa fa-star" aria-hidden="true"></i> {{ imageName }}</h6>
                    <p v-if="isEditMode" class="hand"><span @click="openEditImageModal(image)"><i class="fa fa-pencil" aria-hidden="true"></i> Edit name</span></p>
                    <p><a :href="image.subdir + '/' + image.path" target="_blank">Full Size</a></p>
                </div>
            </div>
        </li><!-- /li -->
        <!-- DELETE IMAGE MODAL -->
        <mapitem-image-delete-modal
                id="deleteImageModal"
                :image="deleteImageModalData"
                @imageDeleteRequested="deleteImage(deleteImageModalData)"
        ></mapitem-image-delete-modal>
        <!-- EDIT IMAGE MODAL -->
        <mapitem-image-edit-modal
                id="editImageModal"
                :image="editImageModalDataCopy"
                @imageEditRequested="editImage(editImageModalDataCopy)"
                @imageEditCanceled="resetImageInformation"
        ></mapitem-image-edit-modal>
    </div>
</template>

<style>
.pod-container{
  width: 100%;
  min-height: 120px;
  border-radius: 15px;
  border: 2px solid black;
  padding: 5px;
}

.image-deleted-border{
    border: 3px solid red;
}

.image-edited-border{
    border: 3px solid green;
}

</style>

<script>
    import MapitemImageDeleteModal from './MapitemImageDeleteModal.vue'
    import MapitemImageEditModal from './MapitemImageEditModal.vue'

  export default {
    mounted() {
      console.log('Image mounted.')
    },
    components: {MapitemImageDeleteModal, MapitemImageEditModal},
    props:{
      image: {
        type: Object,
        required: true
      },
      isEditMode: {
        default: false
      }
    },
    data: function() {
      return {
          deleteImageModalData: null,
          editImageModalDataCopy: null,
          isImageDeleted: false,
          isImageDeletedError: false,
          isImageEdited: false,
          isImageEditedError: false,
          uploadsThumbnailURL: '/media/cache/resolve/squared_thumbnail/uploads/map/',
      }
    },
      computed: {
        imageName: function(){
            return this.editImageModalDataCopy != null ? this.editImageModalDataCopy.name : this.image.name
        },
          actionType: function(){
            if(this.isImageEditedError){
                return 'Update'
            }
            if(this.isImageDeletedError){
                return 'Delete'
            }
            return ''
          }
      },
    methods: {
        deleteImage: function(image){
            let self = this

            axios.delete('/api/mapitemimages/' + image.id)
                .then(function(response){
                    // mark the deleted image in a colored border for 1.5 seconds, then remove it from the record
                    self.isImageDeleted = true
                    setTimeout(function(){
                        self.imageDeleteRequested() // function to emit notice of deletion to parent
                        self.isImageDeleted = false
                    }, 1500)
                })
                .catch(function(error){
                    self.isImageDeletedError = true
                    setTimeout(function(){
                        self.isImageDeletedError = false
                    }, 5000)
                })
        },
        editImage: function(image){
            let self = this

            axios.put('/api/mapitemimage/rename', {image: image})
                .then(function(response){
                    // mark the updated image in a colored border for 1.5 seconds, then remove the border
                    self.isImageEdited = true
                    //self.imageDeleteRequested()
                    setTimeout(function(){
                        self.isImageEdited = false
                    }, 3000)
                })
                .catch(function(error){
                    // restore original image information
                    self.resetImageInformation()
                    self.isImageEditedError = true
                    setTimeout(function(){
                        self.isImageEditedError = false
                    }, 5000)
                })
        },
        imageDeleteRequested: function(){
            this.$emit('imageDeleteRequested', this.editImageModalDataCopy)
        },
        openDeleteImageModal(image){
            this.deleteImageModalData = JSON.parse(JSON.stringify(image))
            $('#deleteImageModal').modal('show')
        },
        openEditImageModal(image){
            this.editImageModalDataCopy = JSON.parse(JSON.stringify(image))
            $('#editImageModal').modal('show')
        },
        resetImageInformation: function(){
            // clear the temp data
            this.editImageModalDataCopy = null
        },
    }
  }
</script>
