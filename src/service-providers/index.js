import generic from "./generic";

const SERVICE_PROVIDERS = {
  generic,
};

export const getServiceProvider = () => {
  return SERVICE_PROVIDERS["generic"];
};
