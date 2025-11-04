<script>
import EloquentField from '@/fields/Filter/EloquentField'
import storage from '@/storage/BelongsToFieldStorage'

export default {
  extends: EloquentField,

  methods: {
    /**
     * Get the resources that may be related to this resource.
     */
    getAvailableResources(search) {
      let queryParams = this.queryParams

      if (search != null) {
        queryParams.first = false
        queryParams.current = null
        queryParams.search = search
      }

      return storage
        .fetchAvailableResources(
          this.filter.resourceName,
          this.field.attribute,
          {
            params: {
              ...queryParams,
              component: this.field.component,
              viaRelationship: this.filter.viaRelationship,
            },
          }
        )
        .then(({ data: { resources, softDeletes, withTrashed } }) => {
          if (!this.isSearchable) {
            this.withTrashed = withTrashed
          }

          this.availableResources = resources
          this.softDeletes = softDeletes
        })
    },
  },
}
</script>
