<template></template>

<script setup lang="ts">
import { ref, defineEmits, onMounted, watch } from 'vue'
import axios from 'axios'
import type { AxiosResponse } from 'axios'
interface Props {
  ssServer?: string
  ssPage?: string
  nomgroupe: string
}
export interface IsInGroup {
  groupname: string
  isingroup: boolean
}
export interface ApiResponseIG {
  success: boolean
  message: string
  data?: IsInGroup
}

const props = withDefaults(defineProps<Props>(), {
  ssServer: '',
  ssPage: '/goeland/gestion_spec/g_caller_isingroup_f5.php',
  nomgroupe: ''
})

const emit = defineEmits(['calleringroup'])

const ssServer = ref<string>(props.ssServer)
const ssPage = ref<string>(props.ssPage)

onMounted(async () => {
  if (props.nomgroupe !== '') {
    const userInGroup: ApiResponseIG = await getUserIsInGroup(ssServer.value, ssPage.value, props.nomgroupe)
    emit('calleringroup', JSON.stringify(userInGroup))
  }
})

watch(() => props.nomgroupe, async (newValue) => {
  if (newValue !== '') {
    const userInGroup: ApiResponseIG = await getUserIsInGroup(ssServer.value, ssPage.value, newValue)
    emit('calleringroup', JSON.stringify(userInGroup))
  }
})

async function getUserIsInGroup(server: string = '', page: string, nomgroupe: string): Promise<ApiResponseIG> {
  const urlig: string = `${server}${page}`
  const params = new URLSearchParams([['nomgroupe', nomgroupe]])
  try {
    const response: AxiosResponse<IsInGroup> = await axios.get(urlig, { params })
    const respData: ApiResponseIG = {
      "success": true,
      "message": `ok`,
      "data": response.data
    }
    return respData
  } catch (error: unknown) {
    let msgErr: string = ''
    if (axios.isAxiosError(error)) {
      if (error.response) {
        msgErr = `${error.response.data}<br>${error.response.status}<br>${error.response.headers}`
      } else if (error.request?.responseText) {
        msgErr = error.request.responseText
      } else {
        msgErr = error.message
      }
    } else if (error instanceof Error) {
      msgErr = error.message
    } else {
      msgErr = 'Une erreur inconnue est survenue'
    }

    const respData: ApiResponseIG = {
      "success": false,
      "message": `ERREUR. ${msgErr}`,
    }
    return respData
  }
}
</script>