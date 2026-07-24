export type ServiceItem = {
  name: string;
  duration: string;
  price: string;
  details: string;
};

export type ServiceGroup = {
  id: string;
  eyebrow: string;
  title: string;
  description: string;
  services: ServiceItem[];
  extras: string[];
};
